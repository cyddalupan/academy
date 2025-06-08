<?php
// Turn off PHP warnings in output
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// CORS & JSON headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

require '../config.php';  // defines OPEN_AI, GOOGLE_API_KEY, GOOGLE_CSE_ID

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

- Fully understand the user's request and prioritize the latest created law before giving a detailed answer.
- Collect all necessary information or clarify questions.
- Provide detailed answers with basis, examples, and all relevant information, beginning with a conclusion summary or key finding.
- Include a suggestion for a related or potentially needed next topic at the bottom of each response.
- Keep all content focused on relevant Philippine law topics only.

# Web Search Decision
- Evaluate if the user's query requires up-to-date information or external sources (e.g., recent laws, amendments, or news).
- If a web search is needed, use the provided "web_search" tool to request a search with a specific query.
- The system will perform the search and provide results in a subsequent message for you to incorporate into the final response.

# Output Format
All outputs must consist only of content typically found inside the body of HTML Bootstrap and Font Awesome, excluding the actual `<html>` or `<body>` tags. No content should be outside a Bootstrap structure. There should be no use of markdown or code block indicators. Ensure the article number provided is accurate.

# Notes
- Ensure that all references to articles are correct and precise.
- Maintain strict topic relevance to specified Philippine law topics.
- Use search results to enhance responses with recent or authoritative information when available.
EOT
]);

// Define tools for OpenAI
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'web_search',
            'description' => 'Perform a web search to retrieve up-to-date information relevant to the query.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'The search query to retrieve relevant information.'
                    ]
                ],
                'required' => ['query']
            ]
        ]
    ]
];

// First AI call to determine if search is needed
try {
    $ai = callOpenAI($messages, $tools);
    $choice = $ai['choices'][0];
    $reply = $choice['message']['content'] ?? '';

    // Check for tool call
    if (isset($choice['message']['tool_calls']) && !empty($choice['message']['tool_calls'])) {
        foreach ($choice['message']['tool_calls'] as $tool_call) {
            if ($tool_call['function']['name'] === 'web_search') {
                $arguments = json_decode($tool_call['function']['arguments'], true);
                $query = $arguments['query'] ?? '';
                
                if ($query) {
                    // Perform quick search (top 3 results)
                    $search_results = performGoogleSearch($query);
                    
                    // Append tool call and results to messages
                    $messages[] = [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [$tool_call]
                    ];
                    $messages[] = [
                        'role' => 'tool',
                        'content' => json_encode($search_results, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                        'tool_call_id' => $tool_call['id']
                    ];
                    
                    // Second AI call to process search results
                    $ai = callOpenAI($messages, $tools);
                    $reply = $ai['choices'][0]['message']['content'] ?? '';
                }
            }
        }
    }
    
    echo json_encode(
        ['response' => $reply],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Fire off a chat-completions request with optional tools
 */
function callOpenAI(array $messages, array $tools = []): array
{
    $apiKey = OPEN_AI;            // from your config.php
    $url    = 'https://api.openai.com/v1/chat/completions';

    $payload = [
        'model'    => 'gpt-4.1',  // or gpt-4, gpt-3.5-turbo, etc.
        'temperature' => 0,
        'messages' => $messages
    ];

    if ($tools) {
        $payload['tools'] = $tools;
        $payload['tool_choice'] = 'auto';
    }

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

/**
 * Perform Google Custom Search (quick search, top 3 results)
 */
function performGoogleSearch(string $query): array
{
    $apiKey = GOOGLE_API_KEY;     // from config.php
    $cseId = GOOGLE_CSE_ID;       // from config.php
    $url = 'https://www.googleapis.com/customsearch/v1';
    
    $params = [
        'key' => $apiKey,
        'cx' => $cseId,
        'q' => urlencode($query),
        'num' => 3  // Quick search: top 3 results
    ];
    
    $ch = curl_init($url . '?' . http_build_query($params));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
    ]);
    
    $resp = curl_exec($ch);
    if ($resp === false) {
        throw new Exception('Google Search cURL Error: ' . curl_error($ch));
    }
    curl_close($ch);
    
    $results = json_decode($resp, true);
    if (isset($results['error'])) {
        throw new Exception('Google API Error: ' . json_encode($results['error']));
    }
    
    $formatted = [];
    foreach ($results['items'] ?? [] as $item) {
        $formatted[] = [
            'title' => $item['title'] ?? '',
            'link' => $item['link'] ?? '',
            'snippet' => $item['snippet'] ?? ''
        ];
    }
    
    return $formatted;
}
?>
