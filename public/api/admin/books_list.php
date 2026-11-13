<?php
require_once __DIR__ . '/../../../includes/auth.php';
requireAdmin(true);

$q      = '%' . trim($_GET['q'] ?? '') . '%';
$status = $_GET['status'] ?? '';

$sql = 'SELECT b.book_id, b.title, b.author, b.isbn, b.book_condition, b.availability,
               b.cover_url, u.full_name AS owner, s.subject_name
        FROM books b
        JOIN users u ON u.user_id = b.owner_id
        JOIN subjects s ON s.subject_id = b.subject_id
        WHERE (b.title LIKE ? OR b.author LIKE ? OR u.full_name LIKE ?)';
$params = [$q, $q, $q];

if (in_array($status, ['available', 'borrowed'], true)) {
    $sql .= ' AND b.availability = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY b.book_id LIMIT 200';

$stmt = getDB()->prepare($sql);
$stmt->execute($params);

jsonResponse(['success' => true, 'books' => $stmt->fetchAll()]);