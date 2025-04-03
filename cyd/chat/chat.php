<?php
require '../config.php';

$apiKey = OPEN_AI;

try {
	$pdo = new PDO($dsn, $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $questionId = $_POST['questionId'];
        $message_6 = $_POST['message_6'];
        $reply_6 = $_POST['reply_6'];
        $message_5 = $_POST['message_5'];
        $reply_5 = $_POST['reply_5'];
        $message_4 = $_POST['message_4'];
        $reply_4 = $_POST['reply_4'];
        $message_3 = $_POST['message_3'];
        $reply_3 = $_POST['reply_3'];
        $message_2 = $_POST['message_2'];
        $reply_2 = $_POST['reply_2'];
        $message_1 = $_POST['message_1'];
        $reply_1 = $_POST['reply_1'];

        $url = 'https://api.openai.com/v1/chat/completions';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];

        $messages = [["role" => "system", "content" => "You are talking to law students about philippine law from 1989 to june 2024, do not talk about other topics, if user off topic just redirect them back to philippine law. also tell them that this website 'TOPBAR ASSIST PH' will help them get better grades at the bar exam"]];

        if (isset($message_6)) {
            $messages[] = [
                "role" => "user",
                "content" => $message_6
            ];
        }
        if (isset($reply_6)) {
            $messages[] = [
                "role" => "system",
                "content" => $reply_6
            ];
        }
        if (isset($message_5)) {
            $messages[] = [
                "role" => "user",
                "content" => $message_5
            ];
        }
        if (isset($reply_5)) {
            $messages[] = [
                "role" => "system",
                "content" => $reply_5
            ];
        }
        if (isset($message_4)) {
            $messages[] = [
                "role" => "user",
                "content" => $message_4
            ];
        }
        if (isset($reply_4)) {
            $messages[] = [
                "role" => "system",
                "content" => $reply_4
            ];
        }
        if (isset($message_3)) {
            $messages[] = [
                "role" => "user",
                "content" => $message_3
            ];
        }
        if (isset($reply_3)) {
            $messages[] = [
                "role" => "system",
                "content" => $reply_3
            ];
        }
        if (isset($message_2)) {
            $messages[] = [
                "role" => "user",
                "content" => $message_2
            ];
        }
        if (isset($reply_2)) {
            $messages[] = [
                "role" => "system",
                "content" => $reply_2
            ];
        }
        if (isset($message_1)) {
            $messages[] = [
                "role" => "user",
                "content" => $message_1
            ];
        }
        if (isset($reply_1)) {
            $messages[] = [
                "role" => "system",
                "content" => $reply_1
            ];
        }

        $postData = json_encode([
            "model" => "gpt-4o-mini",
            "messages" => $messages,
        ]);
        echo "<pre>". print_r($postData) . "</pre>";

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
        $response = json_decode($response, true);
        $latest_reply =  $response['choices'][0]['message']['content'];

        if (isset($reply_5)) {
            $reply_6 = $reply_5;
            $message_6 = $message_5;
        }
        if (isset($reply_4)) {
            $reply_5 = $reply_4;
            $message_5 = $message_4;
        }
        if (isset($reply_3)) {
            $reply_4 = $reply_3;
            $message_4 = $message_3;
        }
        if (isset($reply_2)) {
            $reply_3 = $reply_2;
            $message_3 = $message_2;
        }
        if (isset($reply_1)) {
            $reply_2 = $reply_1;
            $message_2 = $message_1;
        }
        $reply_1 = $latest_reply;
    }
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
    <title>Chatbox</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        .chat-bubble {
            max-width: 75%;
            margin: 5px 0;
            padding: 10px;
            border-radius: 15px;
        }

        .chat-bubble-receive {
            background-color: #f1f1f1;
            margin-right: auto;
        }

        .chat-bubble-send {
            background-color: #007bff;
            color: white;
            margin-left: auto;
        }

        .chat-container {
            height: 236px;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="container mt-3">
    <div class="chat-container border p-3 mb-2">
        <div class="d-flex">
            <div class="chat-bubble chat-bubble-receive">
                Hello! How can I help you?
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <div class="chat-bubble chat-bubble-send">
                I have a question about my order.
            </div>
        </div>
        <div class="d-flex">
            <div class="chat-bubble chat-bubble-receive">
                Sure, what would you like to know?
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <div class="chat-bubble chat-bubble-send">
                When will it be delivered?
            </div>
        </div>
        <div class="d-flex">
            <div class="chat-bubble chat-bubble-receive">
                It's scheduled for tomorrow.
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <div class="chat-bubble chat-bubble-send">
                Great, thank you!
            </div>
        </div>
    </div>

    <!-- Chat Input -->
     <form method="post" action="">
        <input type="hidden" name="reply_6" value="<?php echo $reply_6; ?>">
        <input type="hidden" name="reply_5" value="<?php echo $reply_5; ?>">
        <input type="hidden" name="reply_4" value="<?php echo $reply_4; ?>">
        <input type="hidden" name="reply_3" value="<?php echo $reply_3; ?>">
        <input type="hidden" name="reply_2" value="<?php echo $reply_2; ?>">
        <input type="hidden" name="reply_1" value="<?php echo $reply_1; ?>">
        <input type="hidden" name="message_6" value="<?php echo $message_6; ?>">
        <input type="hidden" name="message_5" value="<?php echo $message_5; ?>">
        <input type="hidden" name="message_4" value="<?php echo $message_4; ?>">
        <input type="hidden" name="message_3" value="<?php echo $message_3; ?>">
        <input type="hidden" name="message_2" value="<?php echo $message_2; ?>">
        <div class="input-group">
            <input type="text" name="message_1" class="form-control" placeholder="Type a message">
            <button class="btn btn-primary" type="submit">Send</button>
        </div>
    </form>
</div>
<script>
    document.querySelector('form').addEventListener('submit', function(event) {
    event.preventDefault();

    const sendButton = event.target.querySelector('button[type="submit"]');
    sendButton.disabled = true;

    const inputField = event.target.querySelector('input[type="text"]');
    const message = inputField.value.trim();

    // If there's a message, proceed with submission
    if (message) {
        // Simulate a form submission
        console.log("Message sent: ", message);

        // Reload the page to send the data or handle it via PHP
        this.submit();

        // Optionally, clear the input field
        // inputField.value = '';
    } else {
        sendButton.disabled = false; // Re-enable button if no message
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.querySelector('.chat-container');
    chatContainer.scrollTop = chatContainer.scrollHeight;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>