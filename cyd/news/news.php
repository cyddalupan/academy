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
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<title>Dashboard</title>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

	<div class="container">
		<div class="header">
			<h1>Total Average Score: <?php echo number_format($score_counts['average'], 2); ?></h1>
		</div>

		<div class="row text-center mb-3">
			<div class="col-md-3">0 - 25: <?php echo $score_counts['0_25']; ?></div>
			<div class="col-md-3">25 - 50: <?php echo $score_counts['25_50']; ?></div>
			<div class="col-md-3">50 - 75: <?php echo $score_counts['50_75']; ?></div>
			<div class="col-md-3">75 - 100: <?php echo $score_counts['75_100']; ?></div>
		</div>

		<div class="row">
			<div class="col-md-3">
				<div class="chart-container">
					<canvas id="scoreChart"></canvas>
				</div>
			</div>

			<div class="col-md-9">
				<div class="summary">
					<h2>Top 3 Students</h2>
					<div class="row">
						<?php foreach ($top_scores as $index => $student): ?>
							<div class="col-4">
								<div class="card mb-3">
									<div class="card-body">
										<h5 class="card-title">Avg Score:
											<?php echo htmlspecialchars(round($student['average_score'])); ?>
										</h5>
										<p class="card-text small">
											<?php echo htmlspecialchars(mb_substr($student['summary'], 0, 200) . (strlen($student['summary']) > 200 ? '...' : '')); ?>
										</p>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="summary">
					<h2>Lowest 3 Students</h2>
					<div class="row">
						<?php foreach ($lowest_scores as $index => $student): ?>
							<div class="col-4">
								<div class="card mb-3">
									<div class="card-body">
										<h5 class="card-title">
											Avg Score: <?php echo htmlspecialchars(round($student['average_score'])); ?>
										</h5>
										<p class="card-text small">
											<?php echo htmlspecialchars(mb_substr($student['summary'], 0, 200) . (strlen($student['summary']) > 200 ? '...' : '')); ?>
										</p>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const ctx = document.getElementById('scoreChart').getContext('2d');
			const scoreChart = new Chart(ctx, {
				type: 'pie',
				data: {
					labels: ['0-25', '25-50', '50-75', '75-100'],
					datasets: [{
						label: 'Scores Distribution',
						data: [
							<?php echo isset($score_counts['0_25']) ? $score_counts['0_25'] : 0; ?>,
							<?php echo isset($score_counts['25_50']) ? $score_counts['25_50'] : 0; ?>,
							<?php echo isset($score_counts['50_75']) ? $score_counts['50_75'] : 0; ?>,
							<?php echo isset($score_counts['75_100']) ? $score_counts['75_100'] : 0; ?>
						],
						backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0']
					}]
				}
			});
		});
	</script>

</body>

</html>