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

    $messages = [["role" => "system", "content" => "Provide a summary based on the following feedback and scores. in less than 400 characters"]];

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