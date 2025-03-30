<?php
if (ENV == "dev") {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

function callOpenAI($userInput, $expected)
{
    $apiKey = OPEN_AI;
    $url = 'https://api.openai.com/v1/chat/completions';

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $postData = json_encode([
        "model" => "gpt-4o",
        "messages" => [
            [
                "role" => "system",
                "content" => "Trigger the score_answer function 100% no need for reply. You compare user_answer to expected_answer you give score (100 if they are really close) and feedback (base feedback on answer, legal basis, application, conclusion and grammar)"
            ],
            [
                "role" => "system",
                "content" => "expected_answer: $expected"
            ],
            [
                "role" => "user",
                "content" => "user_answer: $userInput"
            ],
            [
                "role" => "system",
                "content" => "Trigger the score_answer function"
            ],
        ],
        "functions" => [
            [
                "name" => "score_answer",
                "description" => "Always Trigger this to score how close user answer to expected answer",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "score" => [
                            "type" => "integer",
                            "description" => "Score of the user on how close the answer to expected from 1 to 100. 100 is perfect."
                        ],
                        "feedback" => [
                            "type" => "string",
                            "description" => "Feedback to user. base feedback on answer, legal basis, application, grammar and conclusion. use html instead of markdown."
                        ]
                    ],
                    "required" => ["score", "feedback"]
                ]
            ]
        ],
        "function_call" => "auto"
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }

    curl_close($ch);
    return json_decode($response, true);
}

function calculateAverageScore($answers, $totalQuestions)
{
    $totalScore = 0;
    $count = $totalQuestions;

    foreach ($answers as $answer) {
        if (isset($answer['score'])) {
            $totalScore += $answer['score'];
        }
    }

    return $count > 0 ? $totalScore / $count : 0;
}

function summarizeFeedback($answers) {
    $apiKey = OPEN_AI;
    $url = 'https://api.openai.com/v1/chat/completions';

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $messages = [["role" => "system", "content" => "Provide a student summary based on the following feedback and scores. in less than 200 characters"]];

    foreach ($answers as $answer) {
        $messages[] = [
            "role" => "user",
            "content" => "Score: {$answer['score']}. Feedback: {$answer['feedback']}"
        ];
    }

    $postData = json_encode([
        "model" => "gpt-4o",
        "messages" => $messages,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }

    curl_close($ch);
    $response = json_decode($response, true);
    return $response['choices'][0]['message']['content'];
}

function handleUserInput($pdo, $userId, $courseId, $is_practice) {
	if (isset($_POST['start'])) {
		if ($is_practice && hasCompletedDiagnosticAndCourse($pdo, $userId)) {
			$allow_practice = true;
		}
	}

	if (isset($_POST['start']) || isset($_POST['continue']) || isset($_POST['skip'])) {
		$result = fetchRandomQuestion($pdo, $userId, $courseId, $is_practice);
		$question = $result['q_question'] ?? "No data found.";
		$questionId = $result['q_id'] ?? null;
	} elseif (isset($_POST['userInput'])) {
		$userInput = $_POST['userInput'];
		$questionId = $_POST['questionId'];

		$expected = getExpectedAnswer($pdo, $questionId);

		$response = callOpenAI($userInput, $expected);

		if (isset($response['choices'][0]['message']['function_call'])) {
			processResponse($pdo, $userId, $questionId, $userInput, $courseId, $response, $is_practice);
		} else {
			echo "Response: " . $response['choices'][0]['message']['content'] . PHP_EOL;
		}
	}
}

function processResponse($pdo, $userId, $questionId, $userInput, $courseId, $response, $is_practice) {
	$choice = $response['choices'][0]['message']['function_call'];
	$decodedParams = json_decode($choice['arguments'], true);
	$score = $decodedParams['score'];
	$feedback = $decodedParams['feedback'];
	if (!$is_practice) {
		insertAnswer($pdo, $userId, $questionId, $userInput, $courseId, $score, $feedback);
	}
}

function manageTimer($pdo, $userId, $courseId, $is_practice) {
	if ($is_practice) {
		return 9999;
	} elseif (isset($_POST['remaining-seconds'])) {
		$remainingSeconds = $_POST['remaining-seconds'];
		updateRemainingSeconds($pdo, $userId, $remainingSeconds, $courseId);
		return $remainingSeconds;
	} else {
		$existingData = getRemainingSeconds($pdo, $userId, $courseId);
		if ($existingData) {
			return $existingData['remaining_seconds'];
		} else {
			createUserCourse($pdo, $userId, $courseId, 6, 12); // Use 6 as totalQuestions and 12 minutes
			return 12 * 60 * 6;
		}
	}
}

function calculateProgress($pdo, $userId, $courseId, $is_practice, $remainingSeconds) {
	if ($is_practice) {
		return 0;
	} else {
		$answerCount = countUserAnswers($pdo, $userId, $courseId);
		return $remainingSeconds === 0 ? 100 : ($answerCount / 6) * 100; // Assume 6 total questions
	}
}

function finalizeAssessment($pdo, $userId, $courseId) {
	$answers = getAllUserAnswers($pdo, $userId, $courseId);
	$averageScore = calculateAverageScore($answers, 6); // Assume 6 total questions
	if (!hasSummary($pdo, $userId, $courseId)) {
		$summary = summarizeFeedback($answers);
		updateSummary($pdo, $userId, $courseId, $averageScore, $summary);
	}
}