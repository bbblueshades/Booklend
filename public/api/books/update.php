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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed.');

$in        = json_decode(file_get_contents('php://input'), true) ?? [];
$bookId    = (int)($in['book_id'] ?? 0);
$title     = trim($in['title'] ?? '');
$author    = trim($in['author'] ?? '');
$isbn      = preg_replace('/[^0-9X]/i', '', $in['isbn'] ?? '');
$subjectId = (int)($in['subject_id'] ?? 0);
$condition = $in['book_condition'] ?? '';

// Validation
if ($bookId <= 0) fail(422, 'Invalid book.');
if ($title === '' || mb_strlen($title) > 255)   fail(422, 'Title is required (max 255 characters).');
if ($author === '' || mb_strlen($author) > 150) fail(422, 'Author is required (max 150 characters).');
if (!in_array($condition, ['As New', 'Fine', 'Good', 'Poor'], true)) fail(422, 'Invalid condition.');if ($isbn !== '' && !preg_match('/^(\d{9}[\dX]|\d{13})$/i', $isbn)) fail(422, 'Invalid ISBN.');

$chk = $pdo->prepare('SELECT 1 FROM subjects WHERE subject_id = ?');
$chk->execute([$subjectId]);
if (!$chk->fetch()) fail(422, 'Please choose a valid subject.');

// Ownership check (a separate SELECT, because UPDATE reports 0 rows when nothing changed)
$own = $pdo->prepare('SELECT 1 FROM books WHERE book_id = ? AND owner_id = ?');
$own->execute([$bookId, $user['user_id']]);
if (!$own->fetch()) fail(404, 'Book not found.');

// The WHERE clause repeats owner_id, so the UPDATE is safe on its own
$upd = $pdo->prepare(
    'UPDATE books
        SET title = ?, author = ?, isbn = ?, subject_id = ?, book_condition = ?
      WHERE book_id = ? AND owner_id = ?'
);
$upd->execute([$title, $author, $isbn === '' ? null : $isbn, $subjectId,
               $condition, $bookId, $user['user_id']]);

echo json_encode(['ok' => true]);