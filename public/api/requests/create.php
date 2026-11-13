<?php
ini_set('display_errors', '0');
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
if ($user['role'] !== 'user') fail(403, 'Only members can borrow books.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed.');

$in     = json_decode(file_get_contents('php://input'), true) ?? [];
$bookId = (int)($in['book_id'] ?? 0);
if ($bookId <= 0) fail(422, 'Invalid book.');

try {
    $pdo->beginTransaction();

    // Lock the book row so two simultaneous requests can't both pass the checks
    $stmt = $pdo->prepare('SELECT book_id, owner_id, title, availability FROM books WHERE book_id = ? FOR UPDATE');
    $stmt->execute([$bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $pdo->rollBack();
        fail(404, 'Book not found.');
    }
    if ((int)$book['owner_id'] === (int)$user['user_id']) {
        $pdo->rollBack();
        fail(403, 'You cannot borrow your own book.');
    }
    if ($book['availability'] !== 'available') {
        $pdo->rollBack();
        fail(409, 'This book is currently unavailable.');
    }

    // Already promised or lent to someone?
    $busy = $pdo->prepare("SELECT 1 FROM borrow_requests WHERE book_id = ? AND status IN ('Approved','Borrowed')");
    $busy->execute([$bookId]);
    if ($busy->fetch()) {
        $pdo->rollBack();
        fail(409, 'This book is currently unavailable.');
    }

    // Does this user already have a pending request for it?
    $dup = $pdo->prepare("SELECT 1 FROM borrow_requests WHERE book_id = ? AND borrower_id = ? AND status = 'Requested'");
    $dup->execute([$bookId, $user['user_id']]);
    if ($dup->fetch()) {
        $pdo->rollBack();
        fail(409, 'You already requested this book.');
    }

    // Create the request
    $ins = $pdo->prepare("INSERT INTO borrow_requests (book_id, borrower_id, status) VALUES (?, ?, 'Requested')");
    $ins->execute([$bookId, $user['user_id']]);
    $requestId = (int)$pdo->lastInsertId();

    // Look up the borrower's name directly, so this doesn't depend on currentUser()
    $nameStmt = $pdo->prepare('SELECT full_name FROM users WHERE user_id = ?');
    $nameStmt->execute([$user['user_id']]);
    $borrowerName = $nameStmt->fetchColumn() ?: 'A member';

    // Notify the owner
    $msg  = mb_substr('New request received: "' . $book['title'] . '" requested by ' . $borrowerName, 0, 255);
    $note = $pdo->prepare('INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)');
    $note->execute([$book['owner_id'], $requestId, $msg]);

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['ok' => true, 'request_id' => $requestId]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    fail(500, 'Database error.');
}