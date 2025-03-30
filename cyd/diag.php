<?php
require 'config.php';
require 'utils.php';
require 'model.php';

if (ENV == "dev") {
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
}

$is_practice = isset($_GET['is_practice']) && $_GET['is_practice'] === 'true';

$timer_minutes = 12;

$answerCount = 0;
$totalQuestions = 6;
$progressPercentage = 0;
$averageScore = 0;
$allow_practice = false;

try {
	$pdo = new PDO($dsn, $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$userId = $_POST['userId'];
		$courseId = $_POST['courseId'];

		if($is_practice) {
			if (isset($_POST['start'])) {
				// First Post check only
				$allow_practice = hasCompletedDiagnosticAndCourse($pdo, $userId);
                echo "Practice ALLOWED". $allow_practice;
			}
		}

		if (isset($_POST['start']) || isset($_POST['continue']) || isset($_POST['skip'])) {
			// Get new Question.
			$result = fetchRandomQuestion($userId, $courseId);
			$question = $result ? $result['q_question'] : "No data found.";
			$questionId = $result ? $result['q_id'] : null;
		} elseif (isset($_POST['userInput'])) {
			// After user answer.
			try {
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
			updateRemainingSeconds($pdo, $userId, $remainingSeconds, $courseId);
		} else {
			// Fetch existing remaining_seconds
			$existingData = getRemainingSeconds($pdo, $userId, $courseId);

			if ($existingData) {
				$remainingSeconds = $existingData['remaining_seconds'];
			} else {
				// No record found, create one
				createUserCourse($pdo, $userId, $courseId, $totalQuestions, $timer_minutes);
				$remainingSeconds = $timer_minutes * 60 * $totalQuestions; // Calculate new remaining seconds
			}
		}

		$answerCount = countUserAnswers($pdo, $userId, $courseId);
		$progressPercentage = ($answerCount / $totalQuestions) * 100;
		// Turn percentage to 100 when no time remaining.
		if (isset($remainingSeconds) && $remainingSeconds == 0) {
			$progressPercentage = 100;
		}
		if ($progressPercentage == 100) {
			$answers = getAllUserAnswers($pdo, $userId, $courseId);
			$averageScore = calculateAverageScore($answers, $totalQuestions);
			if (!hasSummary($pdo, $userId, $courseId)) {
				$summary = summarizeFeedback($answers);
				updateSummary($pdo, $userId, $courseId, $averageScore, $summary);
			}
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <?php // require 'style.php'; ?>
</head>

<body>
    <?php if (isset($questionId) && $progressPercentage !== 100 && (!isset($_POST['userInput']) || isset($_POST['skip'])) && !$is_practice): ?>
    <div id="timer" class="d-flex justify-content-center fixed-top bg-white p-2 rounded border"
        style="margin: auto;width: 89px;top: 7px;box-shadow: 4px 10px 15px;">
        <div class="badge bg-primary mx-1">00</div>
        <div>:</div>
        <div class="badge bg-primary mx-1">00</div>
    </div>
    <?php endif; ?>
    <div id="content" class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">Quiz Question</div>

            <div class="card-body">
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $progressPercentage !== 100 && !$is_practice): ?>
                <div class="progress mb-3">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercentage; ?>%;"
                        aria-valuenow="<?php echo $progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-center mb-3">
                    <?php echo $answerCount; ?> out of <?php echo $totalQuestions; ?> questions answered
                </div>
                <?php endif; ?>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userInput']) && $progressPercentage !== 100 && !isset($_POST['skip'])): ?>
                <!-- Result Page -->
                <div class="alert alert-secondary p-3">
                    <p><strong>Score:</strong> <?php echo htmlspecialchars($score); ?></p>
                    <p><strong>Feedback:</strong> <?php echo nl2br($feedback); ?></p>
                    <form method="post" action="">
                        <input type="hidden" name="userId" id="userIdInput">
                        <input type="hidden" name="courseId" id="courseIdInput">
                        <button type="submit" name="continue" class="btn btn-primary">Continue</button>
                    </form>
                </div>
                <?php elseif (!isset($userId)): ?>
                <!-- Start Page -->
                <div class="mb-3 lead">
                    <?php if ($is_practice): ?>
                    <p>Start your practice exam to be well-prepared for the bar.</p>
                    <?php else: ?>
                    <p>Start your diagnostic exam to evaluate your knowledge and skills.</p>
                    <?php endif; ?>
                </div>
                <form method="post" action="">
                    <input type="hidden" name="userId" id="userIdInput">
                    <input type="hidden" name="courseId" id="courseIdInput">
                    <button type="submit" name="start" class="btn btn-primary">
                        <?php if ($is_practice): ?>
                        Start Practice Exam
                        <?php else: ?>
                        Start Diagnostic Exam
                        <?php endif; ?>
                    </button>
                </form>
                <?php elseif ((isset($questionId) && $progressPercentage !== 100) && (!$is_practice || $allow_practice)): ?>
                <!-- Q&A Page -->
                <form method="post" action="">
                    <input type="hidden" name="userId" id="userIdInput">
                    <input type="hidden" name="courseId" id="courseIdInput">
                    <input type="hidden" name="questionId" value="<?php echo htmlspecialchars($questionId); ?>">
                    <input type="hidden" id="remaining-seconds" name="remaining-seconds">
                    <div class="form-group mb-3">
                        <p><?php echo htmlspecialchars($question); ?></p>
                        <textarea name="userInput" class="form-control bg-light border" rows="4"
                            placeholder="Your answer here..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Answer</button>
                    <button type="submit" name="skip" class="btn btn-secondary ms-2">Skip</button>
                </form>
                <?php elseif ((isset($questionId) && $progressPercentage !== 100) && !$allow_practice): ?>
                <div class="alert alert-warning" role="alert">
                    You can't take the practice exam at the moment. You need to complete the diagnostic first and finish
                    one course.
                </div>
                <?php endif; ?>

                <?php if (!empty($answers)): ?>
                <h3>Grade: <?php echo $averageScore; ?> / 100 </h3>
                <div class="accordion" id="accordionExample">
                    <?php foreach ($answers as $index => $answer): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-<?php echo $index; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-<?php echo $index; ?>" aria-expanded="false"
                                aria-controls="collapse-<?php echo $index; ?>">
                                Score: <?php echo $answer['score']; ?>
                            </button>
                        </h2>
                        <div id="collapse-<?php echo $index; ?>" class="accordion-collapse collapse"
                            aria-labelledby="heading-<?php echo $index; ?>" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <p><strong>Question:</strong> <?php echo htmlspecialchars($answer['q_question']); ?></p>
                                <hr>
                                <p><strong>Answer:</strong> <?php echo htmlspecialchars($answer['answer']); ?></p>
                                <hr>
                                <p><strong>Feedback:</strong> <?php echo nl2br($answer['feedback']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div id="loadingSpinner" class="spinner-border text-primary position-absolute top-50 start-50" role="status"
            style="display: none;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <?php require 'scripts.php'; ?>
</body>

</html>