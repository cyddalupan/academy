<?php
use PHPUnit\Framework\TestCase;

class MailTest extends TestCase
{
    public function testFileIsReadableAndWithoutSyntaxErrors()
    {
        $this->assertFileIsReadable(__DIR__ . '/../../cyd/mail.php');
        require_once __DIR__ . '/../../cyd/mail.php';
    }
}
