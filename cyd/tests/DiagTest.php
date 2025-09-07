<?php
use PHPUnit\Framework\TestCase;

if (!defined('ENV')) {
    define('ENV', 'test');
}
if (!defined('OPEN_AI')) {
    define('OPEN_AI', 'test_key');
}

class DiagTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFileIsReadable(__DIR__ . '/../../cyd/diag.php');
        require_once __DIR__ . '/../../cyd/diag.php';
    }
}
