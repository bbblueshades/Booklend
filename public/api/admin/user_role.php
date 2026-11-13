<?php
require_once __DIR__ . '/../../../includes/auth.php';
requireAdmin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfHeader()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
}

$in     = jsonInput();
$userId = (int) ($in['user_id'] ?? 0);
$role   = $in['role'] ?? '';

if (!in_array($role, ['user', 'admin'], true) || $userId < 1) {
    jsonResponse(['success' => false, 'message' => 'Invalid role.'], 422);
}
if ($userId === currentUser()['user_id']) {
    jsonResponse(['success' => false, 'message' => 'You cannot change your own role.'], 422);
}

$stmt = getDB()->prepare('UPDATE users SET role = ? WHERE user_id = ?');
$stmt->execute([$role, $userId]);

jsonResponse(['success' => true, 'message' => 'Record successfully saved.']);