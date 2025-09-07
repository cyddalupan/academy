<?php
use PHPUnit\Framework\TestCase;

if (!defined('DSN_PATH')) {
    define('DSN_PATH', 'sqlite::memory:');
}

class ResultTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../../cyd/result/result.php');
        $_GET['id'] = 1;
        require_once __DIR__ . '/../../../cyd/result/result.php';
    }
}
