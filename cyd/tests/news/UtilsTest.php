<?php
use PHPUnit\Framework\TestCase;

class News_UtilsTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../../cyd/news/utils.php');
        require_once __DIR__ . '/../../../cyd/news/utils.php';
    }
}