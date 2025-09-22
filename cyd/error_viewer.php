<?php
// Security check
$expected_token = 'a3k9d2p5j8f1g7h4';
$provided_token = isset($_GET['token']) ? $_GET['token'] : '';

if ($provided_token !== $expected_token) {
    header('HTTP/1.1 403 Forbidden');
    die('Access Denied');
}

echo "<h1>Error Log Viewer</h1>";

function display_log($file_path, $lines = 500) {
    echo "<h2>Displaying last $lines lines of: " . htmlspecialchars($file_path) . "</h2>";
    
    if (file_exists($file_path) && is_readable($file_path)) {
        // Read all lines into an array
        $all_lines = file($file_path, FILE_IGNORE_NEW_LINES);
        
        if ($all_lines === false) {
            echo "<p>Could not read file.</p>";
            return;
        }
        
        // Get the last N lines
        $last_lines = array_slice($all_lines, -$lines);
        
        // Display the lines
        echo "<pre style='background-color: #f4f4f4; border: 1px solid #ddd; padding: 10px; white-space: pre-wrap; word-wrap: break-word;'>";
        if (empty($last_lines)) {
            echo "Log file is empty.";
        } else {
            foreach ($last_lines as $line) {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>Log file not found or not readable.</p>";
    }
}

$root_log = '/workspaces/academy/error_log';
$cyd_log = '/workspaces/academy/cyd/error_log';

display_log($root_log);
display_log($cyd_log);

?>