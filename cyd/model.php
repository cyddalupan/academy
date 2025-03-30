<?php
if (ENV == "dev") {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}


function fetchRandomQuestion($pdo, $userId, $courseId, $is_practice = false)
{
    if ($is_practice) {
        $query = "
        SELECT q.q_id, q.q_question, q.q_answer 
        FROM quiz_new q
        ORDER BY RAND()
        LIMIT 1";
    } else {
        $query = "
        SELECT q.q_id, q.q_question, q.q_answer 
        FROM quiz_new q
        WHERE q.q_course_id = :courseId AND NOT EXISTS (
            SELECT 1 FROM diag_ans d 
            WHERE d.question_id = q.q_id AND d.user_id = :userId AND d.batch_id = :courseId
        )
        ORDER BY RAND()
        LIMIT 1";
    }

    $stmt = $pdo->prepare($query);
    if (!$is_practice) {
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':courseId', $courseId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

function getExpectedAnswer($pdo, $questionId)
{
    $query = "SELECT q_answer FROM quiz_new WHERE q_id = :questionId";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':questionId', $questionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function insertAnswer($pdo, $userId, $questionId, $userInput, $course_id, $score, $feedback)
{
    $query = "INSERT INTO diag_ans (user_id, batch_id, question_id, answer, score, feedback, date_created) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $course_id, $questionId, $userInput, $score, $feedback]);
}

function countUserAnswers($pdo, $userId, $courseId)
{
    $query = "
    SELECT COUNT(*) AS answer_count 
    FROM diag_ans 
    WHERE user_id = :userId AND batch_id = :courseId";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':courseId', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['answer_count'] : 0;
}

function getAllUserAnswers($pdo, $userId, $courseId)
{
    $query = "
    SELECT da.*, q.q_question
    FROM diag_ans da
    JOIN quiz_new q ON da.question_id = q.q_id
    WHERE da.user_id = :userId AND da.batch_id = :courseId";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':courseId', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
}

function getRemainingSeconds($pdo, $userId, $course_id) {
    $query = "SELECT remaining_seconds FROM custom_users_course WHERE user_id = :userId AND course_id = :course_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createUserCourse($pdo, $userId, $courseId, $totalQuestions, $timer_minutes)
{
    $remainingSeconds = ($timer_minutes * 60) * $totalQuestions; 
    $query = "INSERT INTO custom_users_course (user_id, course_id, remaining_seconds, date_created)
              VALUES (:userId, :courseId, :remainingSeconds, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':courseId', $courseId, PDO::PARAM_INT);
    $stmt->bindParam(':remainingSeconds', $remainingSeconds, PDO::PARAM_INT);
    return $stmt->execute();
}

function updateRemainingSeconds($pdo, $userId, $remainingSeconds, $courseId, $timer_minutes, $totalQuestions)
{
    $remainingSeconds = ($timer_minutes * 60) * $totalQuestions; 

    $query = "UPDATE custom_users_course SET remaining_seconds = :remainingSeconds WHERE user_id = :userId AND course_id = :courseId";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':remainingSeconds', $remainingSeconds, PDO::PARAM_INT);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':courseId', $courseId, PDO::PARAM_INT);
    return $stmt->execute();
}

function updateSummary($pdo, $userId, $course_id, $average, $summary)
{
    $query = "UPDATE custom_users_course SET average_score = :average, summary = :summary WHERE user_id = :userId AND course_id = :course_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':average', $average, PDO::PARAM_STR);
    $stmt->bindParam(':summary', $summary, PDO::PARAM_STR);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    return $stmt->execute();
}

function hasSummary($pdo, $userId, $course_id) {
    $query = "SELECT COUNT(*) FROM custom_users_course WHERE user_id = :userId AND course_id = :course_id AND summary IS NOT NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

function hasCompletedDiagnosticAndCourse($pdo, $userId) {
    $queryDiagnostic = "
    SELECT COUNT(*) FROM custom_users_course 
    WHERE user_id = :userId AND course_id = 0 AND summary IS NOT NULL";
    
    $queryCourse = "
    SELECT COUNT(*) FROM custom_users_course 
    WHERE user_id = :userId AND course_id != 0 AND summary IS NOT NULL";
    
    // Check for diagnostic completion
    $stmt = $pdo->prepare($queryDiagnostic);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $diagnosticCompleted = $stmt->fetchColumn() > 0;

    // Check for at least one course completion
    $stmt = $pdo->prepare($queryCourse);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $courseCompleted = $stmt->fetchColumn() > 0;

    return (bool)($diagnosticCompleted && $courseCompleted);
}

function getUserEmail($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    return $stmt->fetchColumn();
}