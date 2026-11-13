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

$in     = json_decode(file_get_contents('php://input'), true) ?? [];
$bookId = (int)($in['book_id'] ?? 0);
if ($bookId <= 0) fail(422, 'Invalid book.');

// Delete only if (a) it's my book and (b) no active request exists
$del = $pdo->prepare(
    "DELETE FROM books
      WHERE book_id = :id AND owner_id = :owner
        AND NOT EXISTS (
            SELECT 1 FROM borrow_requests r
             WHERE r.book_id = :id2
               AND r.status IN ('Requested','Approved','Borrowed'))"
);
$del->execute([':id' => $bookId, ':owner' => $user['user_id'], ':id2' => $bookId]);

if ($del->rowCount() === 1) {
    echo json_encode(['ok' => true]);
    exit;
}

// Nothing deleted: work out why
$own = $pdo->prepare('SELECT 1 FROM books WHERE book_id = ? AND owner_id = ?');
$own->execute([$bookId, $user['user_id']]);

if (!$own->fetch()) fail(404, 'Book not found.');   // doesn't exist OR belongs to someone else
fail(409, 'Not allowed while a request is active.');