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
        "temperature" => 0,
        "messages" => [
            [
                "role" => "system",
                "content" => <<<EOD
                Compare the `user_answer` to `expected_answer` and provide an accurate score and detailed feedback.

                - **Scoring Criteria:**
                - **Full Alignment:** Provide a score of 100 for answers that fully convey the same content and logical value, even if wording differs.
                - **Strong Similarity:** Use a scale of 70-95 for answers that closely align in content and value but have minor differences.
                - **Mismatch:** For unrelated answers, provide a score of 0-30.
                
                - Base the score on the following dimensions:
                - **Answer**
                - **Legal Basis**
                - **Application**
                - **Conclusion and grammar** 

                - **Feedback:**
                - For each dimension, provide feedback that analyzes strengths and areas for improvement.
                - Include additional recommendations as "Other Suggestions" without points.

                # Output Format

                Provide the score as an integer and detailed feedback separated by dimensions. Use bullet points for feedback under each dimension.

                # Notes

                - Ensure no dimension guarantees a perfect score without thorough evaluation.
                - Adjust the scale to reflect nuanced differences in content and logical alignment rather than exact wording.
                EOD
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
                        "description" => <<<EOD
                        Provide feedback for the user based on specified criteria, formatted in a GetBootstrap HTML table. Each row should include the specific basis, explanation, and score for that criterion. All scores should be 5/5 for each criterion.

                        # Criteria

                        - **Answer**: Evaluate the response's ability to answer the question posed. Full credit if the answer fully addresses the question.
                        - **Legal Basis**: Analyze the statement of doctrine (law, jurisprudence, or both). Ensure the necessary legal elements are determined for completeness before evaluation.
                        - **Application**: Assess how doctrines are applied to the facts in the question. Identify and link relevant facts appropriately.
                        - **Conclusion & Grammar**: Evaluate the response's conclusion. If the student’s conclusion differs from the predetermined one but is considered correct, full credit should be awarded.  Assess grammatical accuracy.

                        Ensure each score is 5/5 with a total score of 25, corresponding to 100% if perfect.

                        # Additional Insights

                        Include any suggestions or observations not covered by the primary criteria. These should be conveyed as additional insights or improvements without a numeric score. If the user scored perfectly, just congratulate them instead.

                        # Output Format

                        The feedback must be formatted as an HTML table, with each row including:
                        - **Basis**: The criterion being evaluated.
                        - **Explanation**: A detailed explanation of the evaluation.
                        - **Score**: The numerical score assigned.

                        The table should be structured like:

                        <table class='table'>
                        <tr>
                        <th>Basis</th>
                        <th>Explanation</th>
                        <th>Score</th>
                        </tr>
                        <tr>
                        <td>[Basis]</td>
                        <td>[Explanation]</td>
                        <td>[Score]</td>
                        </tr>
                        [Additional Rows for Each Criterion]
                        </table>

                        After the table, add a section called 'Additional Insights' as plain text (no numeric score). Share helpful feedback or tips there. But if the user scored perfectly, just congratulate them instead.
                        EOD
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

function summarizeFeedback($answers)
{
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

function ai_email_diagnose($answers, $fullname)
{
    $apiKey = OPEN_AI;
    $url = 'https://api.openai.com/v1/chat/completions';

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $messages = [["role" => "system", "content" => "Provide a student (name:" . $fullname . ") an assessment email (just the body of the email in HTML format) content based on the following feedback and scores, but do not follow the feedback format, this needs to convince the student to use our online course 'TopBar Asssist PH'. note: the result will be emailed dirrectly to do not put variable or text thats needed to be changed"]];

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

function processResponse($pdo, $userId, $questionId, $userInput, $courseId, $response, $is_practice, &$score, &$feedback)
{
    $choice = $response['choices'][0]['message']['function_call'];
    $decodedParams = json_decode($choice['arguments'], true);
    $score = $decodedParams['score'];
    $feedback = $decodedParams['feedback'];
    if (!$is_practice) {
        insertAnswer($pdo, $userId, $questionId, $userInput, $courseId, $score, $feedback);
    }
}

function manageTimer($pdo, $userId, $courseId, $is_practice, $totalQuestions, $timer_minutes)
{
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

function calculateProgress($pdo, $userId, $courseId, $is_practice, $remainingSeconds, $totalQuestions, &$answerCount)
{
    if ($is_practice) {
        return 0;
    } else {
        $answerCount = countUserAnswers($pdo, $userId, $courseId);
        return $remainingSeconds === 0 ? 100 : ($answerCount / $totalQuestions) * 100;
    }
}

function finalizeAssessment($pdo, $userId, $courseId, $totalQuestions, &$answers, &$averageScore)
{
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
