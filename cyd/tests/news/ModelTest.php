<?php
use PHPUnit\Framework\TestCase;

class News_ModelTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../../cyd/news/model.php');
        require_once __DIR__ . '/../../../cyd/news/model.php';
    }
}
