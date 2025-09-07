<?php
use PHPUnit\Framework\TestCase;

if (!defined('ENV')) {
    define('ENV', 'test');
}
if (!defined('OPEN_AI')) {
    define('OPEN_AI', 'test_key');
}

class UtilsTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../cyd/utils.php');
        require_once __DIR__ . '/../../cyd/utils.php';
    }
}
