<?php
use PHPUnit\Framework\TestCase;

class News_ScriptTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../../cyd/news/script.php');
        $score_counts = [
            '0_25' => 0,
            '25_50' => 0,
            '50_75' => 0,
            '75_100' => 0,
            'average' => 0,
        ];
        require_once __DIR__ . '/../../../cyd/news/script.php';
    }
}
