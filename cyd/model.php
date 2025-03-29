<?php
if (ENV == "dev") {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}


function fetchRandomQuestion($userId)
{
    global $pdo;
    $query = "
	SELECT q.q_id, q.q_question, q.q_answer 
	FROM quiz_new q
	WHERE NOT EXISTS (
	    SELECT 1 FROM diag_ans d 
	    WHERE d.question_id = q.q_id AND d.user_id = :userId AND d.batch_id = 0
	)
	ORDER BY RAND()
	LIMIT 1";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result : null;
}

function countUserAnswersBatchZero($pdo, $userId)
{
    $query = "
    SELECT COUNT(*) AS answer_count 
    FROM diag_ans 
    WHERE user_id = :userId AND batch_id = 0";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['answer_count'] : 0;
}

function getAllUserAnswersBatchZero($pdo, $userId)
{
    $query = "
    SELECT da.*, q.q_question
    FROM diag_ans da
    JOIN quiz_new q ON da.question_id = q.q_id
    WHERE da.user_id = :userId AND da.batch_id = 0";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
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

function createUserCourse($pdo, $userId, $totalQuestions, $timer_minutes)
{
    $remainingSeconds = $timer_minutes * 60 * $totalQuestions; // 12 minutes in seconds
    $query = "INSERT INTO custom_users_course (user_id, course_id, remaining_seconds, date_created)
              VALUES (:userId, 0, :remainingSeconds, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':remainingSeconds', $remainingSeconds, PDO::PARAM_INT);
    return $stmt->execute();
}

function updateRemainingSeconds($pdo, $userId, $remainingSeconds)
{
    $query = "UPDATE custom_users_course SET remaining_seconds = :remainingSeconds WHERE user_id = :userId AND course_id = 0";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':remainingSeconds', $remainingSeconds, PDO::PARAM_INT);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
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