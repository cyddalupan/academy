<?php
use PHPUnit\Framework\TestCase;

if (!defined('ENV')) {
    define('ENV', 'test');
}

class ModelTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../cyd/model.php');
        require_once __DIR__ . '/../../cyd/model.php';
    }
}
