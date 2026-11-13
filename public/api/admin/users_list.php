<?php
require_once __DIR__ . '/../../../includes/auth.php';
requireAdmin(true);

$q = '%' . trim($_GET['q'] ?? '') . '%';
$stmt = getDB()->prepare(
    'SELECT user_id, full_name, email, role, created_at
     FROM users WHERE full_name LIKE ? OR email LIKE ?
     ORDER BY user_id LIMIT 200'
);
$stmt->execute([$q, $q]);

jsonResponse(['success' => true, 'users' => $stmt->fetchAll()]);