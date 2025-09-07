<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('ENV')) {
    define('ENV', 'test');
}
if (!defined('OPEN_AI')) {
    define('OPEN_AI', 'test_key');
}
if (file_exists(__DIR__ . '/test.db')) {
    unlink(__DIR__ . '/test.db');
}
if (!defined('DSN_PATH')) {
    define('DSN_PATH', 'sqlite:' . __DIR__ . '/test.db');
}

// Define dummy variables that are expected to be in config.php
if (!defined('USERNAME')) {
    define('USERNAME', 'test');
}
if (!defined('PASSWORD')) {
    define('PASSWORD', 'test');
}

$pdo = new PDO(DSN_PATH, USERNAME, PASSWORD);
$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);
