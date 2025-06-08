<?php
// exam.php
header('Content-Type: application/json');

require '../config.php';   // defines $dsn, $username, $password, ENV, OPEN_AI, etc.
require '../utils.php';    // callOpenAI(), processResponse(), etc.
require '../model.php';   // fetchRandomQuestion(), getExpectedAnswer(), insertAnswer(), etc.

define('EXAM_TIMER_MINUTES', 12);
define('EXAM_MAX_QUESTIONS', 6);
define('DIAG_MAX_QUESTIONS', 8);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $userId = isset($input['userId']) ? (int)$input['userId'] : 0;
    $examId = isset($input['examId']) ? (int)$input['examId'] : 0;

    if (!$action || !$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing action or userId']);
        exit;
    }

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($action) {
        case 'questions':
            $questions = [];
            $totalQuestions = 0;
            $totalTime = 0;

            if ($examId == 0) {
                // 1. Get all answered questions for this user (batch_id=0)
                $stmt = $pdo->prepare("
                    SELECT da.question_id, da.answer, da.feedback, da.score, q.q_question
                    FROM diag_ans da
                    JOIN quiz_new q ON da.question_id = q.q_id
                    WHERE da.user_id = :userId AND da.batch_id = 0
                ");
                $stmt->execute(['userId' => $userId]);
                $answered = [];
                $answeredIds = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $answered[] = [
                        'id' => (int)$row['question_id'],
                        'content' => $row['q_question'],
                        'answer' => $row['answer'],
                        'feedback' => $row['feedback'],
                        'score' => is_null($row['score']) ? null : (int)$row['score']
                    ];
                    $answeredIds[] = (int)$row['question_id'];
                }

                // 2. For remaining slots, pick random unanswered questions from any course
                $slotsLeft = DIAG_MAX_QUESTIONS - count($answered);
                if ($slotsLeft > 0) {
                    $q = $pdo->prepare("
                        SELECT q_id, q_question FROM quiz_new
                        " . (count($answeredIds) ? "WHERE q_id NOT IN (" . implode(',', array_map('intval', $answeredIds)) . ")" : "") . "
                        ORDER BY RAND() LIMIT :maxq
                    ");
                    $q->bindValue(':maxq', $slotsLeft, PDO::PARAM_INT);
                    $q->execute();
                    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
                        $answered[] = [
                            'id' => (int)$row['q_id'],
                            'content' => $row['q_question'],
                            'answer' => '',
                            'feedback' => null,
                            'score' => null
                        ];
                    }
                }
                $questions = $answered;
                $totalQuestions = count($questions);
                $totalTime = EXAM_TIMER_MINUTES * 60 * $totalQuestions;
            } else {
                // 1. Get all answered questions for this user/exam
                $stmt = $pdo->prepare("
                    SELECT da.question_id, da.answer, da.feedback, da.score, q.q_question
                    FROM diag_ans da
                    JOIN quiz_new q ON da.question_id = q.q_id
                    WHERE da.user_id = :userId AND da.batch_id = :examId
                ");
                $stmt->execute(['userId' => $userId, 'examId' => $examId]);
                $answered = [];
                $answeredIds = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $answered[] = [
                        'id' => (int)$row['question_id'],
                        'content' => $row['q_question'],
                        'answer' => $row['answer'],
                        'feedback' => $row['feedback'],
                        'score' => is_null($row['score']) ? null : (int)$row['score']
                    ];
                    $answeredIds[] = (int)$row['question_id'];
                }

                // 2. For remaining slots, pick random unanswered questions from that course
                $slotsLeft = EXAM_MAX_QUESTIONS - count($answered);
                if ($slotsLeft > 0) {
                    $q = $pdo->prepare("
                        SELECT q_id, q_question FROM quiz_new
                        WHERE q_course_id = :examId
                        " . (count($answeredIds) ? "AND q_id NOT IN (" . implode(',', array_map('intval', $answeredIds)) . ")" : "") . "
                        ORDER BY RAND() LIMIT :maxq
                    ");
                    $q->bindValue(':examId', $examId, PDO::PARAM_INT);
                    $q->bindValue(':maxq', $slotsLeft, PDO::PARAM_INT);
                    $q->execute();
                    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
                        $answered[] = [
                            'id' => (int)$row['q_id'],
                            'content' => $row['q_question'],
                            'answer' => '',
                            'feedback' => null,
                            'score' => null
                        ];
                    }
                }
                $questions = $answered;
                $totalQuestions = count($questions);
                $totalTime = EXAM_TIMER_MINUTES * 60 * $totalQuestions;
            }

            // Timer: fetch or create
            $stmt = $pdo->prepare("SELECT remaining_seconds FROM custom_users_course WHERE user_id = :userId AND course_id = :examId");
            $stmt->execute(['userId' => $userId, 'examId' => $examId]);
            $courseData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($courseData) {
                $remaining = (int)$courseData['remaining_seconds'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO custom_users_course (user_id, course_id, remaining_seconds, date_created) VALUES (:userId, :examId, :remaining, NOW())");
                $stmt->execute([
                    'userId' => $userId,
                    'examId' => $examId,
                    'remaining' => $totalTime
                ]);
                $remaining = $totalTime;
            }

            echo json_encode([
                'questions' => $questions,
                'totalExamTimeSeconds' => $totalTime,
                'remainingTimeSeconds' => $remaining
            ]);
            break;

        case 'answer':
            $questionId = isset($input['questionId']) ? (int)$input['questionId'] : 0;
            $answerContent = $input['answerContent'] ?? '';
            $timestamp = $input['timestamp'] ?? '';
            $remainingTime = isset($input['remainingTime']) ? (int)$input['remainingTime'] : null;

            if (!$questionId || $remainingTime === null || !$timestamp) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing questionId, timestamp or remainingTime']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE custom_users_course SET remaining_seconds = :remaining WHERE user_id = :userId AND course_id = :examId");
            $stmt->execute([
                'remaining' => $remainingTime,
                'userId' => $userId,
                'examId' => $examId
            ]);

            $exp = getExpectedAnswer($pdo, $questionId);
            $expected = $exp['q_answer'] ?? '';

            $response = callOpenAI($answerContent, $expected);

            $score = 0;
            $feedback = '';
            processResponse($pdo, $userId, $questionId, $answerContent, $examId, $response, false, $score, $feedback);

            echo json_encode([
                'feedback' => $feedback,
                'score' => $score
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
            exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
