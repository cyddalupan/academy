<?php
use PHPUnit\Framework\TestCase;

$_GET['token'] = 'a3k9d2p5j8f1g7h4';

require_once __DIR__ . '/../error_viewer.php';

class ErrorViewerTest extends TestCase
{
    private $test_log_file;

    protected function setUp(): void
    {
        $this->test_log_file = __DIR__ . '/test_log.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->test_log_file)) {
            unlink($this->test_log_file);
        }
    }

    public function testCleanLogFile()
    {
        $today = date('d-M-Y');
        $yesterday = date('d-M-Y', strtotime('-1 day'));

        $log_content = "[$today 10:00:00] Today\'s log entry\n";
        $log_content .= "[$yesterday 12:00:00] Yesterday\'s log entry\n";
        $log_content .= "No date here\n";
        $log_content .= "[$today 14:00:00] Another entry for today\n";

        file_put_contents($this->test_log_file, $log_content);

        clean_log_file($this->test_log_file);

        $cleaned_content = file_get_contents($this->test_log_file);

        $this->assertStringContainsString("[$today 10:00:00] Today\'s log entry", $cleaned_content);
        $this->assertStringNotContainsString("[$yesterday 12:00:00] Yesterday\'s log entry", $cleaned_content);
        $this->assertStringContainsString("No date here", $cleaned_content);
        $this->assertStringContainsString("[$today 14:00:00] Another entry for today", $cleaned_content);
    }

    public function testDisplayLog()
    {
        $log_content = "Test log entry 1\nTest log entry 2";
        file_put_contents($this->test_log_file, $log_content);

        ob_start();
        display_log($this->test_log_file);
        $output = ob_get_clean();

        $this->assertStringContainsString("<h2>Displaying all lines of: " . htmlspecialchars($this->test_log_file) . "</h2>", $output);
        $this->assertStringContainsString("Test log entry 1", $output);
        $this->assertStringContainsString("Test log entry 2", $output);
    }
}
