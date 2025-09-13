<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

class LawGptPremiumTest extends TestCase
{
    public function testCallOpenAISuccess()
    {
        $messages = [
            [
                'role' => 'user',
                'content' => 'Hello, who are you?'
            ]
        ];

        $response = callOpenAI($messages);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('choices', $response);
        $this->assertIsArray($response['choices']);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
        $this->assertNotEmpty($response['choices'][0]['message']['content']);
    }

    /*
    public function testCallOpenAIError()
    {
        $this->expectException(Exception::class);

        // Temporarily undefine the API key to trigger an error
        $apiKey = OPENAI_API_KEY;
        runkit7_constant_remove('OPENAI_API_KEY');
        define('OPENAI_API_KEY', '');

        try {
            callOpenAI([]);
        } finally {
            // Restore the original API key
            runkit7_constant_remove('OPENAI_API_KEY');
            define('OPENAI_API_KEY', $apiKey);
        }
    }
    */
}
