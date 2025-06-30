<?php
// offline-payment-status.php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

require '../config.php'; // provides $dsn, $username, $password

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed: ' . $e->getMessage()]);
    exit;
}

// Accept user_id from GET
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

// Check for paid record in offline_payment
$stmt = $pdo->prepare(
    "SELECT 1
     FROM offline_payment
     WHERE user_id = :user_id
       AND amount IS NOT NULL
       AND TRIM(amount) <> ''
       AND CAST(amount AS DECIMAL(20,2)) > 0
     LIMIT 1"
);
$stmt->execute(['user_id' => $user_id]);
$hasPaid = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;

echo json_encode(['paid' => $hasPaid]);
