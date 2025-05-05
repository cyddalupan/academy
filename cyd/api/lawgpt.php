<?php
// Turn off PHP warnings in output
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// CORS & JSON headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

require '../config.php';  // defines OPEN_AI

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Decode incoming JSON
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Pull chat history
$conversation = $input['conversation'] ?? [];

// Build GPT “messages” array
$messages = [];
foreach ($conversation as $m) {
    // map your “from” field to GPT role
    $f = strtolower(trim($m['from'] ?? 'user'));
    if (in_array($f, ['assistant','bot','ai'], true)) {
        $role = 'assistant';
    } elseif ($f === 'system') {
        $role = 'system';
    } else {
        $role = 'user';
    }

    // safely coerce text into a string
    $raw = $m['text'] ?? '';
    if (is_string($raw)) {
        $content = $raw;
    } elseif (is_scalar($raw)) {
        $content = (string)$raw;
    } elseif (is_array($raw) || is_object($raw)) {
        $content = json_encode($raw, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    } else {
        $content = '';
    }

    $messages[] = [
        'role'    => $role,
        'content' => $content
    ];
}

array_unshift($messages, [
    'role'    => 'system',
    'content' => <<<'EOT'
You are LawGPT running inside 'TOPBAR ASSIST PH', an online course for law students. Discuss only Philippine law from 1989 to June 2024 or from this website. Redirect any off-topic questions back to this subject. If possible, promote this site as a helpful bar-exam study resource.

- Collect all necessary information or clarify the question before giving an answer.
- Provide detailed answers with basis, examples, and all relevant information.
- At the bottom of each response, suggest a related or potentially needed next topic for the user.

- Use HTML Bootstrap to structure and style your responses.
- Use emojis to enhance clarity and emphasis.
- Ensure responses remain focused on relevant Philippine law topics only.
- Encourage users to utilize the site as a study tool.
EOT
]);

// Send to OpenAI
try {
    $ai = callOpenAI($messages);
    $reply = $ai['choices'][0]['message']['content'] ?? '';
    echo json_encode(
      ['response' => $reply],
      JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Fire off a normal chat-completions request (no function calls)
 */
function callOpenAI(array $messages): array
{
    $apiKey = OPEN_AI;            // from your config.php
    $url    = 'https://api.openai.com/v1/chat/completions';

    $payload = [
        'model'    => 'o4-mini',  // or gpt-4, gpt-3.5-turbo, etc.
        'messages' => $messages
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload)
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    curl_close($ch);

    $decoded = json_decode($resp, true);
    if (isset($decoded['error'])) {
        throw new Exception('OpenAI API Error: ' . json_encode($decoded['error']));
    }
    return $decoded;
}
