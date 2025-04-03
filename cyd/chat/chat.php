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
    </style>
</head>
<body>

<div class="container mt-3">
    <div class="border p-3" style="height: 400px; overflow-y: auto;">
        <!-- Received Message -->
        <div class="d-flex">
            <div class="chat-bubble chat-bubble-receive">
                Hello! How can I help you?
            </div>
        </div>
        <!-- Sent Message -->
        <div class="d-flex justify-content-end">
            <div class="chat-bubble chat-bubble-send">
                I have a question about my order.
            </div>
        </div>
    </div>

    <!-- Chat Input -->
    <form class="mt-2">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Type a message">
            <button class="btn btn-primary" type="submit">Send</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>