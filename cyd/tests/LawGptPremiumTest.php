<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/mocks/callAI.php';

class LawGptPremiumTest extends TestCase
{
    public function testCallXAIWithWebSearchAndHighReasoning()
    {
        $messages = [
            ['role' => 'user', 'content' => 'What is the latest supreme court ruling on cyberlibel?']
        ];
        $web_search = true;
        $high_reasoning = true;

        $result = callXAI($messages, $web_search, $high_reasoning);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('choices', $result);
    }

    public function testCallXAIWithoutWebSearchAndHighReasoning()
    {
        $messages = [
            ['role' => 'user', 'content' => 'What is the penalty for theft?']
        ];
        $web_search = false;
        $high_reasoning = false;

        $result = callXAI($messages, $web_search, $high_reasoning);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('choices', $result);
    }
}
