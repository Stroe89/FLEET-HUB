<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

try {
    // Generate a unique token
    $token = bin2hex(random_bytes(16));
    $userId = $_SESSION['user_id'];
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store the share token
    $sql = "INSERT INTO share_links (user_id, token, expiry) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $userId, $token, $expiry);
    $stmt->execute();
    
    // Generate shareable URL - update with your actual domain
    $shareUrl = "https://" . $_SERVER['HTTP_HOST'] . "/view_shared_company.php?token=" . $token;
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'url' => $shareUrl]);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}