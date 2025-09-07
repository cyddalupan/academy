<?php
use PHPUnit\Framework\TestCase;

class ScriptsTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../cyd/scripts.php');
        $remainingSeconds = 0;
        require_once __DIR__ . '/../../cyd/scripts.php';
    }
}
