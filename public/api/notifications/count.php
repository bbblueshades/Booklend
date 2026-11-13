<?php
ini_set('display_errors', '0');
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/notify.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

try {
    $uid = (int)$user['user_id'];
    createDueSoonNotifications($pdo, $uid);
    echo json_encode(['count' => unreadNotificationCount($pdo, $uid)]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}