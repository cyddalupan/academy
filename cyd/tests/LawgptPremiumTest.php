<?php
use PHPUnit\Framework\TestCase;

class LawgptPremiumTest extends TestCase
{
    public function testWebSearchSuccess()
    {
        // Define the constant to bypass production code paths (DB, headers)
        if (!defined('GEMINI_TEST_MODE')) {
            define('GEMINI_TEST_MODE', true);
        }
        
        // Set test_mode for callTavily and callXAI to return mock data
        $_GET['test_mode'] = 'true';

        // Mock the input that file_get_contents('php://input') would read
        $mock_input = [
            'thread_id' => '123',
            'user_id' => 1,
            'web_search' => true,
            'conversation' => [
                ['from' => 'user', 'text' => 'What is the capital of the Philippines?']
            ]
        ];
        $GLOBALS['mock_file_get_contents'] = function($path) use ($mock_input) {
            if ($path === 'php://input') {
                return json_encode($mock_input);
            }
            return '';
        };

        // Capture the script's output
        ob_start();
        require __DIR__ . '/../api/lawgpt_premium.php';
        $output = ob_get_clean();
        
        // The mock AI response includes the messages sent to it.
        // We assert that the mock Tavily result is part of the conversation.
        $this->assertStringContainsString('Mock Tavily Result', $output);
        
        // Clean up globals
        unset($GLOBALS['mock_file_get_contents']);
        unset($_GET['test_mode']);
    }
}
?>