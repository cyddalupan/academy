<?php
use PHPUnit\Framework\TestCase;

class StyleOverTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../cyd/style-over.php');
        require_once __DIR__ . '/../../cyd/style-over.php';
    }
}
