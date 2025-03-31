<?php

function getLast200CustomUsersCourses($pdo)
{
	$query = "SELECT * FROM custom_users_course ORDER BY id DESC LIMIT 200";
	$stmt = $pdo->query($query);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTop3Scores($pdo)
{
	$query = "SELECT * FROM (SELECT * FROM custom_users_course WHERE average_score IS NOT NULL AND average_score > 0 ORDER BY id DESC LIMIT 200) AS last_courses ORDER BY average_score DESC LIMIT 3";
	$stmt = $pdo->query($query);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLowest3Scores($pdo)
{
	$query = "SELECT * FROM (SELECT * FROM custom_users_course WHERE average_score IS NOT NULL AND average_score > 0 ORDER BY id DESC LIMIT 200) AS last_courses ORDER BY average_score ASC LIMIT 3";
	$stmt = $pdo->query($query);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getQuizData($pdo) {
    $sql = "
    SELECT quiz.q_id, quiz.q_question, quiz.q_answer, quiz.q_level, quiz.q_timer,
           AVG(diag.score) as average_score, COUNT(diag.question_id) as take_count, course.title
    FROM quiz_new AS quiz
    LEFT JOIN diag_ans AS diag ON quiz.q_id = diag.question_id
    LEFT JOIN course ON quiz.q_course_id = course.id
    GROUP BY quiz.q_id
    ORDER BY average_score ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}