<?php
require_once __DIR__ . '/../bootstrap.php';
use PHPUnit\Framework\TestCase;

if (!defined('ENV')) {
    define('ENV', 'test');
}
if (!defined('DSN_PATH')) {
    define('DSN_PATH', 'sqlite::memory:');
}

class NewsTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../../cyd/news/news.php');
        require_once __DIR__ . '/../../../cyd/news/news.php';
    }
}