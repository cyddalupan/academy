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
    $answers = getUserAnswers($pdo, $userId);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Data</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .collapse {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Student Exam Results</h2>

        <?php
        // Group by batch_id (course)
        $groupedAnswers = [];
        foreach ($answers as $answer) {
            $groupedAnswers[$answer['batch_id']][] = $answer;
        }

        // Display results grouped by course
        foreach ($groupedAnswers as $batchId => $results) {
            $courseName = ($batchId == 0) ? "Diagnostic Course" : "Course $batchId"; // Adjust course naming
            $totalScore = 0;
            $totalQuestions = count($results);

            echo "<div class='card mb-3'>";
            echo "<div class='card-header' id='heading-$batchId'>";
            echo "<h5 class='mb-0'>";
            echo "<button class='btn btn-link' onclick='toggleCollapse(\"collapse-$batchId\")'>";
            echo $courseName . " (Avg Score: " . number_format($totalScore / ($totalQuestions ?: 1), 2) . ")";
            echo "</button></h5></div>";

            echo "<div id='collapse-$batchId' class='collapse'>";
            echo "<div class='card-body'>";

            // Display individual results and calculate total score
            foreach ($results as $result) {
                echo "<div class='border rounded p-2 mb-2'>";
                echo "<h6>Question: " . $result['q_question'] . "</h6>";
                echo "<p><strong>Your Answer:</strong> " . $result['answer'] . "</p>";
                echo "<p><strong>Feedback:</strong> " . $result['feedback'] . "</p>";
                echo "<p><strong>Score:</strong> " . $result['score'] . "</p>";
                echo "</div>";

                $totalScore += $result['score']; // Accumulate score
            }

            // Final average score calculation
            $averageScore = $totalQuestions ? ($totalScore / $totalQuestions) : 0;
            echo "<h6>Final Average Score for $courseName: " . number_format($averageScore, 2) . "</h6>";

            echo "</div></div></div>";
        }
        ?>

    </div>

    <script>
        function toggleCollapse(id) {
            const collapseElement = document.getElementById(id);
            if (collapseElement.style.display === "block") {
                collapseElement.style.display = "none";
            } else {
                collapseElement.style.display = "block";
            }
        }
    </script>
</body>

</html>