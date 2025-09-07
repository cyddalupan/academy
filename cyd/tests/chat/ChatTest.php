<?php
use PHPUnit\Framework\TestCase;

if (!defined('ENV')) {
    define('ENV', 'test');
}
if (!defined('OPEN_AI')) {
    define('OPEN_AI', 'test_key');
}

class ChatTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../../cyd/chat/chat.php');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        require_once __DIR__ . '/../../../cyd/chat/chat.php';
    }
}

