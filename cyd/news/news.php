<?php
require '../config.php';

$dsn = DSN_PATH;

// Model Functions
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
// utils function
function countScoreByGroup($courses_results)
{
	$score_0_25 = $score_25_50 = $score_50_75 = $score_75_100 = 0;
	$totalScore = 0;
	$count = count($courses_results);

	foreach ($courses_results as $course) {
		$score = $course['average_score'];
		$totalScore += $score;

		if ($score >= 0 && $score < 25) {
			$score_0_25++;
		} elseif ($score >= 25 && $score < 50) {
			$score_25_50++;
		} elseif ($score >= 50 && $score < 75) {
			$score_50_75++;
		} elseif ($score >= 75 && $score <= 100) {
			$score_75_100++;
		}
	}

	$averageScore = $count ? ($totalScore / $count) : 0;

	return [
		'0_25' => $score_0_25,
		'25_50' => $score_25_50,
		'50_75' => $score_50_75,
		'75_100' => $score_75_100,
		'average' => $averageScore,
	];
}

try {
	$pdo = new PDO($dsn, $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$courses_results = getLast200CustomUsersCourses($pdo);
	$score_counts = countScoreByGroup($courses_results);
	$top_scores = getTop3Scores($pdo);
	$lowest_scores = getLowest3Scores($pdo);

} catch (PDOException $e) {
	echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			background-color: #f4f4f4;
			margin: 0;
			padding: 20px;
		}

		.container {
			max-width: 1200px;
			margin: auto;
		}

		.header {
			text-align: center;
			padding: 20px;
			background-color: #007bff;
			color: white;
		}

		.card {
			background: white;
			border-radius: 8px;
			padding: 20px;
			margin: 10px 0;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
		}

		.flex {
			display: flex;
			justify-content: space-between;
		}

		.chart-container {
			width: 100%;
			height: 400px;
			margin: 20px 0;
		}

		.group {
			display: inline-block;
			width: 22%;
			padding: 10px;
			background: #e7e7e7;
			border-radius: 5px;
			text-align: center;
			margin: 10px 0;
		}

		.summary {
			margin: 20px 0;
		}

		.summary h3 {
			margin-bottom: 0;
		}

		.summary p {
			margin: 5px 0;
		}
	</style>
	 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

	<div class="container">
		<div class="header">
			<h1>Total Average Score: <?php echo number_format($score_counts['average'], 2); ?></h1>
		</div>

		<div class="flex">
			<div class="group">0 - 25: <?php echo $score_counts['0_25']; ?></div>
			<div class="group">25 - 50: <?php echo $score_counts['25_50']; ?></div>
			<div class="group">50 - 75: <?php echo $score_counts['50_75']; ?></div>
			<div class="group">75 - 100: <?php echo $score_counts['75_100']; ?></div>
		</div>

		<div class="chart-container">
			<!-- Include your pie chart library here; for example, using Chart.js -->
			<canvas id="scoreChart"></canvas>
		</div>

		<div class="summary">
			<h2>Top 3 Students</h2>
			<?php foreach ($top_scores as $index => $student): ?>
				<div class="card">
					<h3><?php echo $index + 1 . '. ' . htmlspecialchars($student['summary']); ?></h3>
					<p>Average Score: <?php echo htmlspecialchars($student['average_score']); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="summary">
			<h2>Lowest 3 Students</h2>
			<?php foreach ($lowest_scores as $index => $student): ?>
				<div class="card">
					<h3><?php echo $index + 1 . '. ' . htmlspecialchars($student['summary']); ?></h3>
					<p>Average Score: <?php echo htmlspecialchars($student['average_score']); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<script>
		// Pie chart implementation using Chart.js or any other library
		const ctx = document.getElementById('scoreChart').getContext('2d');
		const scoreChart = new Chart(ctx, {
			type: 'pie',
			data: {
				labels: ['0-25', '25-50', '50-75', '75-100'],
				datasets: [{
					label: 'Scores Distribution',
					data: [<?php echo "$score_0_25, $score_25_50, $score_50_75, $score_75_100"; ?>],
					backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0']
				}]
			}
		});
	</script>

</body>

</html>