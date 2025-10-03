<?php
// ===== Global Error and Shutdown Handler =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_log'); // Log to cyd/error_log

// Catch fatal errors like memory limit exceeded
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $message = "[FATAL] {$error['message']} in {$error['file']} on line {$error['line']}";
        error_log($message);
        // When a fatal error occurs, we can't send a normal JSON response
        // because the script is already terminating.
    }
});

// Catch non-fatal errors (warnings, notices)
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    $severity_str = match($severity) {
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
        default => 'Unknown Error',
    };
    $log_path = ini_get('error_log');
    if ($log_path) {
        error_log(
            "[" . date("d-M-Y H:i:s") . "] [{$severity_str}] {$message} in {$file} on line {$line}" . PHP_EOL,
            3, // Append to the specified file
            $log_path
        );
    }
    return true; // Don't execute the internal PHP error handler
});

// ===== Main Script =====
ini_set('max_execution_time', 300); // 5 minutes

define('MAX_PAYLOAD_CHARS', 100000);

// Decode incoming JSON
$raw_input = isset($GLOBALS['mock_file_get_contents']) ? $GLOBALS['mock_file_get_contents']('php://input') : file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: [];

// Check payload size before processing
if (strlen($raw_input) > MAX_PAYLOAD_CHARS) {
    http_response_code(413); // Payload Too Large
    echo json_encode(['error' => 'The input is too large to process. Please reduce the size of your message.']);
    exit;
}

if (!defined('GEMINI_TEST_MODE')) {


// CORS & JSON headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config.php';  // defines X_AI, $dsn, $username, $password

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize PDO
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$thread_id = $input['thread_id'] ?? '';
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$conversation = $input['conversation'] ?? [];
$web_search = isset($input['web_search']) ? (bool)$input['web_search'] : false;
$high_reasoning = isset($input['high_reasoning']) ? (bool)$input['high_reasoning'] : false;

// Validate input
if (!$thread_id || !$user_id || !$conversation) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing thread_id, user_id, or conversation']);
    exit;
}

// Build messages array and store user messages in database
$messages = [];
$recap_messages = [];
foreach ($conversation as $m) {
    $from = strtolower(trim($m['from'] ?? 'user'));
    $text = $m['text'] ?? '';
    $role = in_array($from, ['assistant', 'bot', 'ai']) ? 'assistant' : ($from === 'system' ? 'system' : 'user');
    
    if (!is_string($text)) {
        $text = is_scalar($text) ? (string)$text : json_encode($text, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    
    // Store user message
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chat_history (thread_id, user_id, `from`, `text`, `role`, created_at)
            VALUES (:thread_id, :user_id, :from, :text, :role, NOW())
        ");
        $stmt->execute([
            'thread_id' => $thread_id,
            'user_id' => $user_id,
            'from' => $from,
            'text' => $text,
            'role' => $role
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save message: ' . $e->getMessage()]);
        exit;
    }
    
    $messages[] = [
        'role' => $role,
        'content' => $text
    ];
    
    // Collect messages for recap
    if ($role !== 'system') {
        $recap_messages[] = [
            'role' => $role,
            'content' => $text
        ];
    }
}

// Prepend system prompt
$todays_date = date("F j, Y");
$system_prompt = <<<EOD
You are lawGPT, an AI assistant specializing in Philippine law. Your goal is to provide high-reasoning, accurate, and up-to-date legal information. Today's date is $todays_date.

Follow these steps:
1.  **Synthesize & Reason:** Analyze the search results (if available). Think step-by-step to construct a detailed and well-structured answer. Explain the legal concepts involved.
2.  **Respond:** Provide the answer in Markdown format only. never reply in other formats like html. The response should be clear, accurate, and address all parts of the user's query.
3. Do not suggest websites or apologize. Do not create or export or ask for files of any kind.

- When citing jurisprudence, always include:
   • G.R. No.  
   • Case caption (petitioner v. respondent)  
   • Date of decision  
   • Division/En Banc  
   • Short syllabus or holding ;;
EOD;

array_unshift($messages, [
    'role' => 'system',
    'content' => $system_prompt
]);

// Get today's message count for the user
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as message_count
        FROM chat_history
        WHERE user_id = :user_id
        AND role = 'user'
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute(['user_id' => $user_id]);
    $message_count = $stmt->fetch(PDO::FETCH_ASSOC)['message_count'];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve message count: ' . $e->getMessage()]);
    exit;
}


/**
 * Get the last user message from an array of messages
 */
function getLastUserMessage(array $messages): string
{
    $last_user_message = '';
    if (!empty($messages)) {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (isset($messages[$i]['role']) && $messages[$i]['role'] === 'user') {
                $last_user_message = $messages[$i]['content'];
                break;
            }
        }
    }
    return $last_user_message;
}

// Get the last user message from the conversation
$last_user_message = getLastUserMessage($messages);

if ($web_search === true) {
    try {
        // Truncate the user message to 390 characters for the Tavily API call
        $truncated_message = substr($last_user_message, 0, 390);
        $tavily_results = callTavily($truncated_message);
        $formatted_results = '';
        if (isset($tavily_results['results']) && is_array($tavily_results['results'])) {
            $char_limit = 8000;
            foreach ($tavily_results['results'] as $result) {
                $next_result = "Title: " . $result['title'] . "\n";
                $next_result .= "Link: " . $result['url'] . "\n";
                $next_result .= "Snippet: " . $result['content'] . "\n\n";
                if (strlen($formatted_results) + strlen($next_result) > $char_limit) {
                    break;
                }
                $formatted_results .= $next_result;
            }
        }
        if (!empty($formatted_results)) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => "Here are the web search results:\n\n" . $formatted_results
            ]);
        }
    } catch (Exception $e) {
        error_log("Tavily API call failed: " . $e->getMessage());
        // Inform the AI that the web search failed so it can notify the user.
        array_unshift($messages, [
            'role' => 'system',
            'content' => "Web search failed with the following error: " . $e->getMessage() . ". Inform the user that you were unable to perform a web search and are answering based on your existing knowledge. Do not show them the error message directly."
        ]);
    }
}

// ===== Payload Truncation Logic =====
$payload_limit = 32000;

// Recalculate size before truncation loop
$current_size = calculate_payload_size($messages);

// 1. First, try to shorten the web search results content if it exists
if ($current_size > $payload_limit) {
    foreach ($messages as $index => &$message) {
        // Identify the search results system message
        if ($message['role'] === 'system' && strpos($message['content'], 'Here are the web search results:') === 0) {
            $original_content_length = strlen($message['content']);
            $excess = $current_size - $payload_limit;
            
            // Calculate how much to keep
            $new_content_length = $original_content_length - $excess;
            
            if ($new_content_length > 0) {
                $message['content'] = substr($message['content'], 0, $new_content_length);
            } else {
                // If the excess is more than the content, remove the message entirely
                unset($messages[$index]);
            }
            
            // Re-index the array and recalculate size
            $messages = array_values($messages);
            $current_size = calculate_payload_size($messages);
            break; // Exit after dealing with search results
        }
    }
    unset($message); // Unset reference
}


// 2. If still over the limit, remove oldest messages (skipping the main system prompt at index 0)
while (calculate_payload_size($messages) > $payload_limit && count($messages) > 1) {
    // Remove the oldest message after the system prompt
    array_splice($messages, 1, 1);
}

// 3. Final safety net: If the last message is still too big, truncate it.
$current_size = calculate_payload_size($messages);
if ($current_size > $payload_limit) {
    $last_message_index = count($messages) - 1;
    
    // Check if there's a message to truncate (other than the initial system prompt)
    if (isset($messages[$last_message_index]) && $messages[$last_message_index]['role'] !== 'system' && $last_message_index > 0) {
        $system_prompts_size = 0;
        // Calculate size of all system prompts
        for ($i = 0; $i < $last_message_index; $i++) {
            if ($messages[$i]['role'] === 'system') {
                $system_prompts_size += strlen($messages[$i]['content']);
            }
        }

        // Leave a small buffer
        $allowed_last_message_size = $payload_limit - $system_prompts_size - 500;

        if ($allowed_last_message_size > 0) {
            $messages[$last_message_index]['content'] = substr($messages[$last_message_index]['content'], 0, $allowed_last_message_size);
        } else {
            // This is an extreme case where system prompts alone exceed the limit.
            error_log('[ERROR] Payload limit is too small for system prompts. Cannot process.');
            http_response_code(400);
            echo json_encode(['error' => 'Request payload is too large to process.']);
            exit;
        }
    }
}
// ===== End of Payload Truncation Logic =====

// Call xAI

try {
        // When using our custom Tavily search, we disable Grok's internal web search
    // to prevent duplicate searches and to rely on our curated search results.
    $grok_web_search = false;

    $start_time = microtime(true);
    $ai = callXAI($messages, $grok_web_search, $high_reasoning);
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    error_log("callXAI execution time: " . $execution_time . " seconds");
    $reply = $ai['choices'][0]['message']['content'] ?? '';
    
    // Store AI response in database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chat_history (thread_id, user_id, `from`, `text`, `role`, created_at)
            VALUES (:thread_id, :user_id, :from, :text, :role, NOW())
        ");
        $stmt->execute([
            'thread_id' => $thread_id,
            'user_id' => $user_id,
            'from' => 'assistant',
            'text' => $reply,
            'role' => 'assistant'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save AI response: ' . $e->getMessage()]);
        exit;
    }
    
    echo json_encode(
        [
            'response' => $reply,
            'message_count_today' => (int)$message_count
        ],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
    );
} catch (Exception $e) {
    error_log("xAI API call failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
} // End of GEMINI_TEST_MODE block

/**
 * Fire off a search request to Tavily
 */
function callTavily(string $query): array
{
    if (isset($_GET['test_mode']) && $_GET['test_mode'] === 'true') {
        return [
            "results" => [
                [
                    "title" => "Mock Tavily Result",
                    "url" => "https://example.com/mock-result",
                    "content" => "This is a mock search result from Tavily."
                ]
            ]
        ];
    }

    $apiKey = TAVILY_API_KEY;
    $url = 'https://api.tavily.com/search';

    $payload = [
        'api_key' => $apiKey,
        'query' => $query,
        'search_depth' => 'advanced',
        'include_answer' => true,
        'max_results' => 5
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($resp === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL Error: ' . $error);
    }
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("Tavily API request failed with status $http_code: $resp");
    }

    $decoded = json_decode($resp, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to decode JSON response from Tavily API: ' . json_last_error_msg() . ". Raw response: " . $resp);
    }

    if (!empty($decoded['error'])) {
        throw new Exception('Tavily API Error: ' . json_encode($decoded['error']));
    }

    return $decoded;
}

/**
 * Calculate the total character count of the 'content' fields in the messages array.
 */
function calculate_payload_size(array $messages): int
{
    $total_chars = 0;
    foreach ($messages as $message) {
        if (isset($message['content'])) {
            $total_chars += strlen($message['content']);
        }
    }
    return $total_chars;
}

/**
 * Fire off a chat-completions request
 */
function callXAI(array $messages, bool $grok_web_search, bool $high_reasoning): array
{
    if (isset($_GET['test_mode']) && $_GET['test_mode'] === 'true') {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is a mock AI response with search results: ' . json_encode($messages)
                    ]
                ]
            ]
        ];
    }

    $apiKey = X_AI;
    $url = 'https://api.x.ai/v1/chat/completions';

    $payload = [
        'model' => 'grok-4',
        'temperature' => 0,
        'messages' => $messages,
        'web_search' => $grok_web_search
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($resp === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL Error: ' . $error);
    }
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("xAI API request failed with status $http_code: $resp");
    }

    $decoded = json_decode($resp, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to decode JSON response from xAI API: ' . json_last_error_msg() . ". Raw response: " . $resp);
    }

    if (!empty($decoded['error'])) {
        throw new Exception('xAI API Error: ' . json_encode($decoded['error']));
    }

    if (empty($decoded['choices'])) {
        throw new Exception('Invalid response from xAI API: "choices" key is missing or empty. Raw response: ' . $resp);
    }

    return $decoded;
}
?>
