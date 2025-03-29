<?php
require 'config.php'

// Database credentials
$dsn = DSN;
$username = DB_USER;
$password = DB_PASS; 
$timer_minutes = 12;

function callOpenAI($userInput, $expected) {
    $apiKey = OPEN_AI; 
    $url = 'https://api.openai.com/v1/chat/completions';

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $postData = json_encode([
        "model" => "gpt-4o",
        "messages" => [
            [
                "role" => "system",
                "content" => "Trigger the score_answer function 100% no need for reply. You compare user_answer to expected_answer you give score (100 if they are really close) and feedback (base feedback on answer, legal basis, application, conclusion and grammar)"
            ],
            [
                "role" => "system",
                "content" => "expected_answer: $expected"
            ],
            [
                "role" => "user",
                "content" => "user_answer: $userInput"
            ],
            [
                "role" => "system",
                "content" => "Trigger the score_answer function"
            ],
        ],
        "functions" => [
            [
                "name" => "score_answer",
                "description" => "Always Trigger this to score how close user answer to expected answer",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "score" => [
                            "type" => "integer",
                            "description" => "Score of the user on how close the answer to expected from 1 to 100. 100 is perfect."
                        ],
                        "feedback" => [
                            "type" => "string",
                            "description" => "Feedback to user. base feedback on answer, legal basis, application, grammar and conclusion. use html instead of markdown."
                        ]
                    ],
                    "required" => ["score", "feedback"]
                ]
            ]
        ],
        "function_call" => "auto"
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }

    curl_close($ch);
    return json_decode($response, true);
}

function fetchRandomQuestion($userId) {
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

function countUserAnswersBatchZero($pdo, $userId) {
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

function getAllUserAnswersBatchZero($pdo, $userId) {
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

function calculateAverageScore($answers, $totalQuestions) {
	$totalScore = 0;
	$count = $totalQuestions;

	foreach ($answers as $answer) {
		if (isset($answer['score'])) {
			$totalScore += $answer['score'];
		}
	}

	return $count > 0 ? $totalScore / $count : 0;
}

function getRemainingSeconds($pdo, $userId) {
    $query = "SELECT remaining_seconds FROM custom_users_course WHERE user_id = :userId AND course_id = 0";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createUserCourse($pdo, $userId, $totalQuestions, $timer_minutes) {
    $remainingSeconds = $timer_minutes * 60 * $totalQuestions; // 12 minutes in seconds
    $query = "INSERT INTO custom_users_course (user_id, course_id, remaining_seconds, date_created)
              VALUES (:userId, 0, :remainingSeconds, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':remainingSeconds', $remainingSeconds, PDO::PARAM_INT);
    return $stmt->execute();
}

function updateRemainingSeconds($pdo, $userId, $remainingSeconds) {
    $query = "UPDATE custom_users_course SET remaining_seconds = :remainingSeconds WHERE user_id = :userId AND course_id = 0";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':remainingSeconds', $remainingSeconds, PDO::PARAM_INT);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

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
	<style>
	    body {
		background-color: #fff;
		font-family: Arial, sans-serif;
	    }
	    .container {
		max-width: 600px;
		margin: 50px auto;
		background-color: #fff;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		border-radius: 5px;
		overflow: hidden;
		text-align: center; /* Centered content */
	    }
	    .card-header {
		background-color: #007bff;
		color: #fff;
		padding: 15px;
		font-size: 1.25rem;
		text-align: center;
	    }
	    .card-body {
		padding: 20px;
	    }
	    .form-group {
		margin-bottom: 15px;
	    }
	    .result {
		background-color: #f1f1f1;
		padding: 15px;
		border-radius: 5px;
		margin-bottom: 15px;
		border-left: 5px solid #007bff;
	    }
        textarea {
            width: 97%;
            height: 300px;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 3px;
            font-size: 1rem;
            resize: vertical;

            background-color: #fdfde3;
            background-image: repeating-linear-gradient(
                to bottom,
                #fdfde3,
                #fdfde3 35px,
                #eceadf 35px,
                #eceadf 37px
            );
        } 
	    button {
            background-color: #007bff;
            color: #fff;
            padding: 12px 30px; /* Bigger button */
            border: none;
            border-radius: 5px; /* More rounded */
            cursor: pointer;
            font-size: 1.1rem; /* Larger font */
            transition: background-color 0.3s;
	    }
	    button:hover {
            background-color: #0056b3; /* Darker blue on hover */
	    }
        #skipButton {
            background-color: #6c757d; /* Grey color for Skip */
            color: #fff;
            padding: 12px 30px; /* Same padding */
            border: none;
            border-radius: 5px; /* Same rounded corners */
            cursor: pointer;
            font-size: 1.1rem; /* Same font size */
            transition: background-color 0.3s;
        }
        #skipButton:hover {
            background-color: #5a6268; /* Darker grey on hover */
        }
	    .instruction {
            margin-bottom: 20px;
            font-size: 1.1rem; /* Slightly larger instruction font */
	    }
        .progress-bar-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            margin: 20px 0;
        }

        .progress-bar {
            height: 20px;
            background-color: #4caf50;
            transition: width 0.3s ease;
        }

        .progress-text {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }

		.accordion {
			background-color: #f7f7f7;
			border-radius: 5px;
			margin-bottom: 10px;
			border: 1px solid #ddd;
		}

		.accordion-item {
			border-bottom: 1px solid #ddd;
		}

		.accordion-item:last-child {
			border-bottom: none;
		}

		.accordion-header {
			padding: 10px;
			cursor: pointer;
			font-weight: bold;
			display: flex;
			justify-content: space-between;
			background-color: #007bff;
			color: white;
			border-radius: 5px;
			margin: 0;
		}

		.accordion-content {
			display: none;
			padding: 10px;
			background-color: white;
			border-top: none;
		}

		.accordion-content p {
			margin: 0;
		}

        .spinner {
            border: 8px solid #f3f3f3; /* Light grey */
            border-top: 8px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            position: absolute; /* This assumes you'll position it over your button */
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000; /* Ensure it's on top of all other elements */
        }

		hr {
		    border: 0;
			height: 1px;
			background-color: #007bff36;
			border-radius: 5px;
			margin: 20px 0;
		}

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Time Style */
       .timer {
            display: flex;
            justify-content: center;
            margin: 10px 0;
            position: fixed;
            background: #ffffff;
            opacity: 0.9;
            left: 45px;
            top: 79px;
            padding: 3px;
            border-radius: 4px;
            border: solid lightgrey 1px;
        } 
        .time {
            font-size: 12px;
            font-weight: bold;
            margin: 0 5px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
        }
        .time-label {
            margin-top: 5px;
        }
	</style>
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
								<img src="https://s2.svgbox.net/hero-outline.svg?ic=chevron-down&color=000" alt="Arrow Down" style="width: 16px; height: 16px;">
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

<script>
// Save ID from Local Storage
document.addEventListener('DOMContentLoaded', function() {
	try {
		const userId = window.parent.localStorage.getItem('user_id');
		const contentDiv = document.getElementById('content');
		const userIdInput = document.getElementById('userIdInput');

		if (userId) {
			console.log('User ID:', userId);
			userIdInput.value = userId;
		} else {
			const message = "Please log in to access the diagnostic exam.";
			console.log(message);
			contentDiv.style.display = 'none';
			document.body.insertAdjacentHTML('afterbegin', `<h1>${message}</h1>`);
		}
	} catch (error) {
		console.error('Error accessing parent localStorage:', error);
	}
});

// Summary Accordion
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', function () {
        const index = this.getAttribute('data-index');
        const content = document.getElementById('content-' + index);
        const allContent = document.querySelectorAll('.accordion-content');

        allContent.forEach(c => {
        if (c !== content) c.style.display = 'none';
        });

        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
        } else {
            content.style.display = 'none';
        }
    });
});

// Set the countdown time (in seconds)
<?php if(isset($remainingSeconds) && (!isset($_POST['userInput']) || isset($_POST['skip']))): ?>
let countdownTime = 10; 
countdownTime = <?php echo $remainingSeconds; ?>; 

const minutesElement = document.getElementById('minutes');
const secondsElement = document.getElementById('seconds');
const remainingSecondsInput = document.getElementById('remaining-seconds');
const submitButton = document.getElementById('submitButton');

const countdown = setInterval(() => {
    if (countdownTime <= 0) {
        clearInterval(countdown);
        submitButton.click();
        return;
    }

    let minutes = Math.floor(countdownTime / 60);
    let seconds = countdownTime % 60;

    minutesElement.textContent = String(minutes).padStart(2, '0');
    secondsElement.textContent = String(seconds).padStart(2, '0');

    countdownTime--;
}, 1000);

// Submit Interceptor
document.addEventListener('DOMContentLoaded', function() {
    const skipButton = document.getElementById('skipButton');
    const loadingSpinner = document.getElementById('loadingSpinner');

    function handleButtonClick() {
        remainingSecondsInput.value = countdownTime >= 0 ? countdownTime : 0; // Save remaining seconds
        loadingSpinner.style.display = 'block'; // Add Spinner
    }

    submitButton.addEventListener('click', handleButtonClick);
    skipButton.addEventListener('click', handleButtonClick);
});
<?php endif; ?>
</script>

</body>
</html>
