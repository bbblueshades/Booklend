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
$requestId = (int)($in['request_id'] ?? 0);
$decision  = $in['decision'] ?? '';
if ($requestId <= 0 || !in_array($decision, ['approve', 'reject'], true)) fail(422, 'Invalid request.');

try {
    $pdo->beginTransaction();

    // FOR UPDATE locks the request and its book while we decide
    $stmt = $pdo->prepare(
        'SELECT r.request_id, r.status, r.borrower_id,
                b.book_id, b.owner_id, b.title, b.availability
           FROM borrow_requests r
           JOIN books b ON b.book_id = r.book_id
          WHERE r.request_id = ? FOR UPDATE'
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Not found and "someone else's" look identical, so IDs can't be probed
    if (!$row || (int)$row['owner_id'] !== (int)$user['user_id']) {
        $pdo->rollBack();
        fail(404, 'Request not found.');
    }
    if ($row['status'] !== 'Requested') {
        $pdo->rollBack();
        fail(409, 'This request has already been handled.');
    }

    if ($decision === 'approve') {
        // Only one borrower at a time per book
        $busy = $pdo->prepare(
            "SELECT 1 FROM borrow_requests
              WHERE book_id = ? AND request_id <> ? AND status IN ('Approved','Borrowed')"
        );
        $busy->execute([$row['book_id'], $requestId]);
        if ($busy->fetch() || $row['availability'] !== 'available') {
            $pdo->rollBack();
            fail(409, 'This book is already promised or lent to someone else.');
        }
        $newStatus = 'Approved';
        $message   = 'Your request was approved: "' . $row['title'] . '". Meet the owner for the handover.';
    } else {
        $newStatus = 'Rejected';
        $message   = 'Your request was rejected: "' . $row['title'] . '".';
    }

    $upd = $pdo->prepare("UPDATE borrow_requests SET status = ? WHERE request_id = ? AND status = 'Requested'");
    $upd->execute([$newStatus, $requestId]);

    $note = $pdo->prepare('INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)');
    $note->execute([$row['borrower_id'], $requestId, mb_substr($message, 0, 255)]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'status' => $newStatus]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    fail(500, 'Database error.');
}