<?php
// Turn off PHP warnings in output
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// CORS & JSON headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

require '../config.php';  // defines $dsn, $username, $password

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize PDO
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get user_id from POST (assuming sent as form data)
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Validate file upload and user_id
if ($user_id <= 0 || !isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id or invalid receipt file']);
    exit;
}

// Validate file type and size (e.g., images only, max 5MB)
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_type = mime_content_type($_FILES['receipt']['tmp_name']);
$file_size = $_FILES['receipt']['size'];
if (!in_array($file_type, $allowed_types) || $file_size > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type or size exceeds 5MB']);
    exit;
}

// Define upload directory (create if not exists)
$upload_dir = '../uploads/receipts/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$filename = 'receipt_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
$target_path = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $target_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload file']);
    exit;
}

// Insert into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO gpt_payments (user_id, receipt_image, status, created_at)
        VALUES (:user_id, :receipt_image, 'pending', NOW())
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'receipt_image' => '/uploads/receipts/' . $filename  // Store relative path for easy frontend access
    ]);
    $insert_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    // Clean up file on failure
    unlink($target_path);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save payment record: ' . $e->getMessage()]);
    exit;
}

// Grant 1-day premium access immediately
try {
    $stmt = $pdo->prepare("
        INSERT INTO gpt_premium (user_id, expiration_date, created_at, updated_at)
        VALUES (:user_id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), NOW(), NOW())
        ON DUPLICATE KEY UPDATE expiration_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY), updated_at = NOW()
    ");
    $stmt->execute(['user_id' => $user_id]);
} catch (PDOException $e) {
    // Optional: Log error, but don't fail the response
}

// Success response
echo json_encode([
    'success' => true,
    'payment_id' => $insert_id,
    'receipt_path' => '/uploads/receipts/' . $filename
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
