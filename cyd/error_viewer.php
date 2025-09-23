<?php
// Security check
$expected_token = 'a3k9d2p5j8f1g7h4';
$provided_token = isset($_GET['token']) ? $_GET['token'] : '';

if ($provided_token !== $expected_token) {
    header('HTTP/1.1 403 Forbidden');
    die('Access Denied');
}

echo "<h1>Error Log Viewer</h1>";

function display_log($file_path) {
    echo "<h2>Displaying all lines of: " . htmlspecialchars($file_path) . "</h2>";
    
    if (file_exists($file_path) && is_readable($file_path)) {
        // Read all lines into an array
        $all_lines = file($file_path, FILE_IGNORE_NEW_LINES);
        
        if ($all_lines === false) {
            echo "<p>Could not read file.</p>";
            return;
        }
        
        // Get all lines
        $last_lines = $all_lines;
        
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

function clean_log_file($file_path) {
    if (!file_exists($file_path) || !is_readable($file_path) || !is_writable($file_path)) {
        echo "<p style='color: red;'>Cannot clean log file: " . htmlspecialchars($file_path) . ". It may not exist or have the correct permissions.</p>";
        return;
    }

    $lines = file($file_path);
    if ($lines === false) {
        echo "<p style='color: red;'>Could not read log file for cleaning: " . htmlspecialchars($file_path) . "</p>";
        return;
    }

    $today_str = date('d-M-Y');
    $kept_lines = [];

    foreach ($lines as $line) {
        // Check if the line contains a date in the format [DD-Mon-YYYY]
        if (preg_match('/^\[(\d{2}-\w{3}-\d{4})/', $line, $matches)) {
            $log_date_str = $matches[1];
            if ($log_date_str === $today_str) {
                $kept_lines[] = $line;
            }
        } else {
            // Keep lines that do not have a date at the beginning
            $kept_lines[] = $line;
        }
    }

    // Overwrite the file with the cleaned content
    $result = file_put_contents($file_path, implode("", $kept_lines));

    if ($result === false) {
        echo "<p style='color: red;'>Failed to write cleaned content to log file: " . htmlspecialchars($file_path) . "</p>";
    } else {
        echo "<p>Cleaned log file: " . htmlspecialchars($file_path) . "</p>";
    }
}

// Use relative paths to ensure portability
$root_log = __DIR__ . '/../error_log';
$cyd_log = __DIR__ . '/error_log';
$api_log = __DIR__ . '/api/error_log';

display_log($root_log);
display_log($cyd_log);
display_log($api_log);

echo "<h1>Cleaning Log Files...</h1>";
clean_log_file($root_log);
clean_log_file($cyd_log);
clean_log_file($api_log);

?>