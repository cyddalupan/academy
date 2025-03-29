<?php
require '../config.php';

$dsn = DSN_PATH;

// Model functions
function getUserAnswers($pdo, $userId): mixed
{
    $query = "
    SELECT da.*, q.q_question
    FROM diag_ans da
    JOIN quiz_new q ON da.question_id = q.q_id
    WHERE da.user_id = :userId";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
	$pdo = new PDO($dsn, $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $userId = $_GET['id'];
	echo "USERID!!!!".$userId;
    $answers = getUserAnswers($pdo, $userId);
    echo '<pre>'; // For better formatting
    print_r($answers);
    echo '</pre>';

} catch (PDOException $e) {
	echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<title>Result</title>
</head>

<body>

</body>

</html>