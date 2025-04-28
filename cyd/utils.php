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
        "model" => "gpt-4.1",
        "messages" => [
            [
                "role" => "system",
                "content" => "Trigger the score_answer function 100% no need for reply. Compare user_answer to expected_answer and provide a score (100 for close answers and 0 for unrelated answers) and feedback based on Conclusion, Legal Basis, Logic, and Grammar & Composition. Include Other Suggestions without points for any additional recommendations not covered above."
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
                            "description" => "Provide feedback for the user based on specified criteria, formatted in an HTML table (Use getbootstrap which is already installed). Each row should include the specific basis, explanation, and score for that criterion. Score sum should equal to total score \n\n# Criteria\n\n- **Conclusion**: Evaluate the response's conclusion. If the student's conclusion differs from the predetermined one but is considered correct by the teacher, full credit should be awarded.\n- **Legal Basis**: Analyze the statement of doctrine (law, jurisprudence, or both). Ensure the necessary legal elements are determined for completeness before evaluation.\n- **Logic**: Connect doctrines to facts in the question, ensuring the logical flow. Relevant facts must be identified and linked appropriately by the teacher.\n- **Grammar & Composition**: Assess grammatical accuracy and composition.\n\nEnsure the total score aligns with a pre-defined scale.\n\n# Additional Insights\n\nInclude any suggestions or observations not covered by the primary criteria. These should be conveyed as additional insights or improvements without a numeric score.\n\n# Output Format\n\nThe feedback must be formatted as an HTML table, with each row including:\n- **Basis**: The criterion being evaluated.\n- **Explanation**: A detailed explanation of the evaluation.\n- **Score**: The numerical score assigned.\n\nThe table should be structured like:\n\n<table>\n    <tr>\n        <th>Basis</th>\n        <th>Explanation</th>\n        <th>Score</th>\n    </tr>\n    <tr>\n        <td>[Basis]</td>\n        <td>[Explanation]</td>\n        <td>[Score]</td>\n    </tr>\n    [Additional Rows for Each Criterion]\n</table>\n\nInclude a section for Additional Insights without a numeric score, formatted as plain text following the table."
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

function ai_email_diagnose($answers, $fullname) {
    $apiKey = OPEN_AI;
    $url = 'https://api.openai.com/v1/chat/completions';

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $messages = [["role" => "system", "content" => "Provide a student (name:".$fullname.") an assessment email (just the body of the email in HTML format) content based on the following feedback and scores, but do not follow the feedback format, this needs to convince the student to use our online course 'TopBar Asssist PH'. note: the result will be emailed dirrectly to do not put variable or text thats needed to be changed"]];

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

function processResponse($pdo, $userId, $questionId, $userInput, $courseId, $response, $is_practice, &$score, &$feedback) {
	$choice = $response['choices'][0]['message']['function_call'];
	$decodedParams = json_decode($choice['arguments'], true);
	$score = $decodedParams['score'];
	$feedback = $decodedParams['feedback'];
	if (!$is_practice) {
		insertAnswer($pdo, $userId, $questionId, $userInput, $courseId, $score, $feedback);
	}
}

function manageTimer($pdo, $userId, $courseId, $is_practice, $totalQuestions, $timer_minutes) {
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
			createUserCourse($pdo, $userId, $courseId, $totalQuestions, $timer_minutes);
			return ($timer_minutes * 60) * $totalQuestions;
		}
	}
}

function calculateProgress($pdo, $userId, $courseId, $is_practice, $remainingSeconds, $totalQuestions, &$answerCount) {
	if ($is_practice) {
		return 0;
	} else {
		$answerCount = countUserAnswers($pdo, $userId, $courseId);
		return $remainingSeconds === 0 ? 100 : ($answerCount / $totalQuestions) * 100;
	}
}

function finalizeAssessment($pdo, $userId, $courseId, $totalQuestions, &$answers, &$averageScore) {
	$answers = getAllUserAnswers($pdo, $userId, $courseId);
	$averageScore = calculateAverageScore($answers, $totalQuestions);
	if (!hasSummary($pdo, $userId, $courseId)) {
		$summary = summarizeFeedback($answers);
		updateSummary($pdo, $userId, $courseId, $averageScore, $summary);
        $user = getCurrentUser($pdo, $userId);
		$ai_email_diagnose = ai_email_diagnose($answers, $user['first_name'] . " " . $user['last_name']);
        send_email_with_phpmailer($pdo, $user['email'], 'Diagnostic Exam', $ai_email_diagnose, 'ehajjonlinephilippines@gmail.com');
	}
}
