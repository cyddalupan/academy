<?php
require 'config.php';
require 'utils.php';
require 'model.php';

if (ENV == "dev") {
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
}

// Database credentials
$dsn = DSN_PATH;
$username = DB_NAME;
$password = DB_PASS;
$timer_minutes = 12;

$answerCount = 0;
$totalQuestions = 6;
$progressPercentage = 0;
$averageScore = 0;

try {
	$pdo = new PDO($dsn, $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['start']) || isset($_POST['continue']) || isset($_POST['skip'])) {
			// Get new Question.
			$userId = $_POST['userId'];
			$result = fetchRandomQuestion($userId);
			$question = $result ? $result['q_question'] : "No data found.";
			$questionId = $result ? $result['q_id'] : null;
		} elseif (isset($_POST['userInput'])) {
			// After user answer.
			try {
				$userId = $_POST['userId'];
				if (!$userId) {
					die("User ID is not set.");
				}
				$userInput = $_POST['userInput'];
				$questionId = $_POST['questionId'];

				$query = "SELECT q_answer FROM quiz_new WHERE q_id = :questionId";
				$stmt = $pdo->prepare($query);
				$stmt->bindParam(':questionId', $questionId, PDO::PARAM_INT);
				$stmt->execute();
				$expected = $stmt->fetchColumn();

				$response = callOpenAI($userInput, $expected);

				if (isset($response['choices'][0]['message']['function_call'])) {
					$choice = $response['choices'][0]['message']['function_call'];
					$decodedParams = json_decode($choice['arguments'], true);
					$score = $decodedParams['score'];
					$feedback = $decodedParams['feedback'];

					$query = "INSERT INTO diag_ans (user_id, batch_id, question_id, answer, score, feedback, date_created) VALUES (?, ?, ?, ?, ?, ?, NOW())";
					$stmt = $pdo->prepare($query);
					$stmt->execute([$userId, 0, $questionId, $userInput, $score, $feedback]);

				} else {
					echo "Response: " . $response['choices'][0]['message']['content'] . PHP_EOL;
				}
			} catch (Exception $e) {
				echo "Error: " . $e->getMessage();
			}
		}
		// Manage Timer
		if (isset($_POST['remaining-seconds'])) {
			// Update the remaining_seconds
			$remainingSeconds = $_POST['remaining-seconds'];
			updateRemainingSeconds($pdo, $userId, $remainingSeconds);
		} else {
			// Fetch existing remaining_seconds
			$existingData = getRemainingSeconds($pdo, $userId);

			if ($existingData) {
				$remainingSeconds = $existingData['remaining_seconds'];
			} else {
				// No record found, create one
				createUserCourse($pdo, $userId, $totalQuestions, $timer_minutes);
				$remainingSeconds = $timer_minutes * 60 * $totalQuestions; // Calculate new remaining seconds
			}
		}

		$answerCount = countUserAnswersBatchZero($pdo, $userId);
		$progressPercentage = ($answerCount / $totalQuestions) * 100;
		// Turn percentage to 100 when no time remaining.
		if (isset($remainingSeconds) && $remainingSeconds == 0) {
			$progressPercentage = 100;
		}
		if ($progressPercentage == 100) {
			$answers = getAllUserAnswersBatchZero($pdo, $userId);
			$averageScore = calculateAverageScore($answers, $totalQuestions);
		}
	}
} catch (PDOException $e) {
	echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>Quiz Application</title>
	<?php require 'style.php'; ?>
</head>

<body>
	<?php if (isset($questionId) && $progressPercentage !== 100 && (!isset($_POST['userInput']) || isset($_POST['skip']))): ?>
		<div id="timer" class="timer">
			<div class="time" id="minutes">00</div>
			<div class="time-label">:</div>
			<div class="time" id="seconds">00</div>
		</div>
	<?php endif; ?>
	<div id="content" class="container">
		<div class="card-header">Quiz Question</div>

		<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $progressPercentage !== 100): ?>
			<div class="progress-bar-container">
				<div class="progress-bar" style="width: <?php echo $progressPercentage; ?>%;"></div>
			</div>
			<div class="progress-text">
				<?php echo $answerCount; ?> out of <?php echo $totalQuestions; ?> questions answered
			</div>
		<?php endif; ?>

		<div class="card-body">
			<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userInput']) && $progressPercentage !== 100 && !isset($_POST['skip'])): ?>
				<!-- Result Page -->
				<div class="result">
					<p><strong>Score:</strong> <?php echo htmlspecialchars($score); ?></p>
					<p><strong>Feedback:</strong> <?php echo nl2br($feedback); ?></p>
					<form method="post" action="">
						<input type="hidden" name="userId" id="userIdInput">
						<button id="submitButton" type="submit" name="continue">Continue</button>
					</form>
				</div>
			<?php elseif (!isset($userId)): ?>
				<!-- Start Page -->
				<div class="instruction">
					<p>Begin your diagnostic exam to assess your knowledge and skills!</p>
				</div>
				<form method="post" action="">
					<input type="hidden" name="userId" id="userIdInput">
					<button id="submitButton" type="submit" name="start">Start Diagnostics</button>
				</form>
			<?php elseif (isset($questionId) && $progressPercentage !== 100): ?>
				<!-- Q&A Page -->
				<form method="post" action="">
					<input type="hidden" name="userId" id="userIdInput">
					<input type="hidden" name="questionId" value="<?php echo htmlspecialchars($questionId); ?>">
					<input type="hidden" id="remaining-seconds" name="remaining-seconds">
					<div class="form-group">
						<p><?php echo htmlspecialchars($question); ?></p>
						<textarea name="userInput" rows="4" placeholder="Your answer here..."></textarea>
					</div>
					<button id="submitButton" type="submit">Submit Answer</button>
					<button id="skipButton" type="submit" name="skip" style="margin-left: 10px;">Skip</button>
				</form>
			<?php endif; ?>

			<?php if (!empty($answers)): ?>
				<h3>Grade: <?php echo $averageScore; ?> / 100 </h3>
				<div class="accordion">
					<?php foreach ($answers as $index => $answer): ?>
						<div class="accordion-item">
							<div class="accordion-header" data-index="<?php echo $index; ?>">
								<span>Score: <?php echo $answer['score']; ?></span>
								<span>
									<img src="https://s2.svgbox.net/hero-outline.svg?ic=chevron-down&color=000" alt="Arrow Down"
										style="width: 16px; height: 16px;">
								</span>
							</div>
							<div class="accordion-content" id="content-<?php echo $index; ?>">
								<p><strong>Question:</strong> <?php echo htmlspecialchars($answer['q_question']); ?></p>
								<hr>
								<p><strong>Answer:</strong> <?php echo htmlspecialchars($answer['answer']); ?></p>
								<hr>
								<p><strong>Feedback:</strong> <?php echo nl2br($answer['feedback']); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<div id="loadingSpinner" class="spinner" style="display:none;"></div>
	</div>
	<?php require 'scripts.php'; ?>
</body>

</html>