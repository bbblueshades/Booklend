<?php
ini_set('display_errors', '0');
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/notify.php';
header('Content-Type: application/json');

function fail(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$user = currentUser();
if (!$user) fail(401, 'Please log in.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed.');

$in   = json_decode(file_get_contents('php://input'), true) ?? [];
$upTo = (int)($in['up_to'] ?? 0);
$uid  = (int)$user['user_id'];

try {
    // user_id comes from the session, so nobody can mark someone else's notifications
    if ($upTo > 0) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1
                                WHERE user_id = ? AND is_read = 0 AND notification_id <= ?');
        $stmt->execute([$uid, $upTo]);
    } else {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$uid]);
    }
    echo json_encode(['ok' => true, 'count' => unreadNotificationCount($pdo, $uid)]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    fail(500, 'Database error.');
}