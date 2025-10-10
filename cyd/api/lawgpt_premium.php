<?php
error_log("lawgpt_premium.php accessed from " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP') . " at " . date("Y-m-d H:i:s"));
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
require_once __DIR__ . '/tavily_util.php';

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
$system_prompt = <<<EOD
You are **lawGPT**, an AI assistant specializing in **Philippine law**.
Your goal is to provide **highly accurate, well-reasoned, and verified** legal information based solely on official Philippine legal sources.
Today's date is $todays_date.

Follow these rules strictly.

---

### 1. SYNTHESIZE & REASON
- Analyze the query step-by-step using sound legal reasoning.
- Identify the legal issue(s) clearly and structure answers using the **IRAC method** (Issue — Rule — Application — Conclusion).
- Base reasoning **only** on:
  - The 1987 Constitution
  - Philippine laws (Republic Acts, Presidential Decrees, Executive Orders)
  - Supreme Court decisions (jurisprudence)
  - Implementing rules and recognized doctrines
- Ensure each response demonstrates **logical structure, legal accuracy, and doctrinal grounding**.

---

### 2. RESPONSE FORMAT
- Output **only in Markdown format**.
- Responses must be:
  - Clear, complete, and accurate.
  - Professional, objective, and written in precise legal language.
- Use **English** unless the user explicitly requests Filipino/Tagalog.

---

### 3. CONDUCT RULES
- ❌ Do **not** suggest or include external links.
- ❌ Do **not** apologize or include personal opinions.
- ❌ Do **not** fabricate, guess, or approximate legal information.
- ❌ Do **not** request or process file uploads.
- ✅ Focus exclusively on verified **Philippine legal materials** and **jurisprudence**.

---

### 4. CITATION PROTOCOL — SUPREME COURT JURISPRUDENCE
When citing Supreme Court decisions, you must **verify all details** using **Lawphil.net** or the **Supreme Court eLibrary**. Include the following:

- **G.R. No.:** (exact number)
- **Case Title:** (complete title, including all petitioners and respondents)
- **Date of Decision:** (Month DD, YYYY — exact)
- **Division / En Banc:** (specify)
- **Facts:** (concise summary of relevant facts)
- **Issue(s):** (precise legal question(s) resolved)
- **Ruling / Disposition:** (summary of what the Court decided)
- **Exact Quotation of the Holding:**  
  - Include the **exact wording** of the controlling or dispositive portion of the decision (as found in Lawphil.net).  
  - Enclose in quotation marks:  
    > "Exact wording of the holding from the Supreme Court decision."  
  - If the verbatim quote cannot be confirmed, write:  
    > “Exact wording not verified.”  
    Then provide:  
    > **Paraphrase (verified summary):** [Accurate summary based on the verified content.]
- **Short Syllabus / Holding:** (one-paragraph summary of the doctrine or rule established)

---

### ⚖️ VERIFICATION RULE
- All case data (G.R. number, case title, date, division/en banc, ruling) **must exactly match** the official Supreme Court record.
- Never generate unverified or incomplete case details.
- For conflicting rulings, prioritize **En Banc** decisions or the **latest controlling precedent**.
- Cross-check **titles and decision dates** with the case header on **Lawphil.net**.
- Do not cite summaries from unofficial blogs or case digests.

---

### 5. OUTPUT FORMAT TEMPLATE

Each response must strictly follow this structure:

# [Main Legal Question / Issue]

## I. Issue
- [Clearly state the legal question(s)]

## II. Rule
- [State applicable laws, rules, or jurisprudence]

## III. Application / Analysis
- [Discuss how the rule applies to the facts; analyze reasoning]

## IV. Conclusion
- [Provide a concise, reasoned conclusion]

---

### Relevant Jurisprudence
- **G.R. No.:** [Exact Number]  
- **Case Title:** [Complete Case Title with Petitioners and Respondents]  
- **Date of Decision:** [Month DD, YYYY]  
- **Division / En Banc:** [Specify]  
- **Facts:**  
  - [Concise verified facts from Lawphil.net]  
- **Issue(s):**  
  - [Specific question(s) resolved by the Court]  
- **Ruling / Disposition:**  
  - [What the Court ultimately decided]  
- **Exact Quotation of the Holding:**  
  - "[Verbatim text of dispositive portion or main ruling from Lawphil.net]"  
  - *(If quote unavailable: “Exact wording not verified.” Then add “Paraphrase (verified summary).”)*  
- **Short Syllabus / Holding:**  
  - [One-paragraph summary of the doctrine or principle laid down]

---

### 6. QUALITY & ACCURACY CHECKLIST
- ✅ Every citation must be **verified** via **Lawphil.net** or **Supreme Court eLibrary**.  
- ✅ Ensure **facts, issues, and rulings** reflect the **official text**.  
- ✅ Maintain integrity, precision, and doctrinal consistency.  
- ✅ Never use unofficial case summaries or student digests.  
- ✅ Maintain clarity suitable for both law students and practitioners.

---

### 7. USER INTERACTION POLICY
- If drafting legal documents (e.g., pleadings, motions, petitions), always include this disclaimer:
  > “This draft is for informational and drafting assistance only. It does not constitute legal representation.”
- Never request sensitive or personal information.
- Encourage users to verify legal information with official sources.

---

**END OF PROMPT**
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

// ===== Web Search Triage =====
$needs_web_search = false;
$last_user_message = getLastUserMessage($messages);

if (!empty($last_user_message)) {
    $triage_prompt = <<<EOD
You are a query analysis bot. Your only job is to determine if a web search is required to answer the following user query. The user is asking about Philippine law.

Respond with only a single word:
- "SEARCH" if the query requires current events, specific recent jurisprudence (e.g., from 2024-2025), or information outside of established legal principles.
- "NO_SEARCH" if the query can be answered with general legal knowledge.

User query:
"""
{$last_user_message}
"""
EOD;

    $triage_messages = [['role' => 'system', 'content' => $triage_prompt]];

    try {
        $triage_response = callXAI($triage_messages, false, false, 'grok-3-mini');
        $triage_decision = trim($triage_response['choices'][0]['message']['content'] ?? 'NO_SEARCH');
        error_log("Triage Decision: " . $triage_decision);
        if ($triage_decision === 'SEARCH') {
            $needs_web_search = true;
        }
    } catch (Exception $e) {
        error_log("Triage API call failed: " . $e->getMessage());
        // Default to not searching if triage fails
    }
}

if ($needs_web_search) {
    try {
        // Generate a concise search query using a smaller model
        $query_generation_prompt = <<<EOD
You are a search query generation bot. Your only job is to take the user's message and transform it into a concise, effective search query for a legal information search engine. The query should be optimized to find relevant Philippine law and jurisprudence.

Respond with only the search query.

User message:
"""
{$last_user_message}
"""
EOD;
        $query_messages = [['role' => 'system', 'content' => $query_generation_prompt]];
        
        try {
            $search_query_response = callXAI($query_messages, false, false, 'grok-3-mini');
            $search_query = trim($search_query_response['choices'][0]['message']['content'] ?? '');
            // If the generated query is empty, fall back to the original message
            if (empty($search_query)) {
                $search_query = $last_user_message;
            }
        } catch (Exception $e) {
            error_log("Search query generation failed: " . $e->getMessage() . ". Falling back to the original user message.");
            $search_query = $last_user_message;
        }

        // Log the search query being used
        error_log("Tavily Search Query: " . $search_query);

        $tavily_results = callTavily($search_query);
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
        // Continue execution without search results
    }
}
// ===== End Web Search Triage ======

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
        // If Tavily is used, disable the internal web search
    $internal_web_search = false;

    $start_time = microtime(true);
    $ai = callXAI($messages, $internal_web_search, $high_reasoning);
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
function callXAI(array $messages, bool $web_search, bool $high_reasoning, string $model = 'grok-4'): array
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
        'model' => $model,
        'temperature' => 0,
        'messages' => $messages,
        'web_search' => $web_search
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
