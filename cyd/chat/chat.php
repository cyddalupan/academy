<?php
require '../config.php';
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
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Type a message">
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>