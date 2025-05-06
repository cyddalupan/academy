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
Ensure all responses contain only the content that would reside within the body of an HTML Bootstrap structure formatted using Font Awesome for icons, and relate solely to Philippine law. Redirect off-topic questions back to relevant subjects and verify compliance with the article number.

- Collect all necessary information or clarify questions before providing an answer.
- Provide detailed answers with basis, examples, and all relevant information.
- Include a suggestion for a related or potentially needed next topic at the bottom of each response.
- Keep all content focused on relevant Philippine law topics only.

# Output Format

All outputs must consist only of content typically found inside the body of HTML Bootstrap and Font Awesome, excluding the actual `<html>` or `<body>` tags. No content should be outside a Bootstrap structure. There should be no use of markdown or code block indicators. Ensure the article number provided is accurate.

# Notes

- Ensure that all references to articles are correct and precise.
- Maintain strict topic relevance to specified Philippine law topics.
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
