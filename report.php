<?php
require 'cyd/config.php';

if (ENV == "dev") {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to get students' average scores with their names
    $stmt = $pdo->prepare(
        "SELECT u.first_name, u.last_name, da.user_id, AVG(da.score) as average_score
     FROM diag_ans da
     JOIN users u ON da.user_id = u.id
     WHERE da.batch_id = 0
     GROUP BY da.user_id"
    );
    $stmt->execute();
    $studentsAverageScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query to get each student's details including their name
    $stmt = $pdo->prepare(
        "SELECT u.first_name, u.last_name, da.user_id, q.q_question, da.answer, da.score, da.feedback
     FROM diag_ans da
     JOIN users u ON da.user_id = u.id
     JOIN quiz_new q ON da.question_id = q.q_id
     WHERE da.batch_id = 0
     ORDER BY da.user_id, da.question_id"
    );
    $stmt->execute();
    $studentsDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .print-button {
            margin-bottom: 20px;
        }

        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }

        .summary-card h3,
        .summary-card p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .no-print {
            display: inline-block;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-button no-print">Print Report</button>

    <h1>Diagnostic Report</h1>

    <h2>Summary of Students' Average Scores</h2>

    <?php foreach ($studentsAverageScores as $student): ?>
        <div class="summary-card">
            <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
            <p>Average Score: <?php echo htmlspecialchars($student['average_score']); ?></p>
        </div>
    <?php endforeach; ?>

    <?php
    $current_student = null;
    foreach ($studentsDetails as $detail):
        if ($current_student !== $detail['user_id']):
            if ($current_student !== null): ?>
                </tbody>
                </table>
            <?php endif; ?>

            <h3><?php echo htmlspecialchars($detail['first_name'] . ' ' . $detail['last_name']); ?>'s Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Answer</th>
                        <th>Score</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>

                    <?php $current_student = $detail['user_id'];
        endif; ?>

                <tr>
                    <td><?php echo htmlspecialchars($detail['q_question']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($detail['answer'])); ?></td>
                    <td><?php echo htmlspecialchars($detail['score']); ?></td>
                    <td><?php echo nl2br($detail['feedback']); ?></td>
                </tr>

            <?php endforeach; ?>

            <?php if ($current_student !== null): ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>