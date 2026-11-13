<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
header('Content-Type: application/json');

function fail(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$user = currentUser();
if (!$user) fail(401, 'Please log in.');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail(405, 'Method not allowed.');

$q       = mb_substr(trim($_GET['q'] ?? ''), 0, 100);
$subject = (int)($_GET['subject'] ?? 0);
$avail   = $_GET['availability'] ?? '';

$where  = [];
$params = [];

if ($q !== '') {
    // escape LIKE wildcards so a typed "%" or "_" is treated literally
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(b.title LIKE :q1 OR b.author LIKE :q2)';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
}
if ($subject > 0) {
    $where[] = 'b.subject_id = :subject';
    $params[':subject'] = $subject;
}
if (in_array($avail, ['available', 'borrowed'], true)) {
    $where[] = 'b.availability = :avail';
    $params[':avail'] = $avail;
}

$sql = 'SELECT b.book_id, b.title, b.author, b.availability, b.cover_url,
               s.subject_name, (b.owner_id = :me) AS is_own
          FROM books b
          JOIN subjects s ON s.subject_id = b.subject_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY b.created_at DESC LIMIT 60';
$params[':me'] = $user['user_id'];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($books as &$b) {
        $b['book_id'] = (int)$b['book_id'];
        $b['is_own']  = (bool)$b['is_own'];
    }
    unset($b);
    echo json_encode(['books' => $books]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    fail(500, 'Database error.');
}