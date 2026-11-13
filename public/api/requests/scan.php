<?php
ini_set('display_errors', '0');
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
header('Content-Type: application/json');

const LOAN_DAYS = 14;

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
$token     = trim((string)($in['token'] ?? ''));
$confirm   = ($in['confirm'] ?? false) === true;     // false = preview only
if ($requestId <= 0 || $token === '' || strlen($token) > 100) fail(422, 'Invalid scan.');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT r.request_id, r.status, r.borrower_id,
                b.book_id, b.owner_id, b.qr_code, b.title
           FROM borrow_requests r
           JOIN books b ON b.book_id = r.book_id
          WHERE r.request_id = ? FOR UPDATE'
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Only the book's owner can scan
    if (!$row || (int)$row['owner_id'] !== (int)$user['user_id']) {
        $pdo->rollBack();
        fail(404, 'Request not found.');
    }

    // The scanned token must belong to THIS book
    if (!hash_equals($row['qr_code'], $token)) {
        $pdo->rollBack();
        fail(422, 'This QR code does not match the book.');
    }

    // The state machine: the current status decides what the scan means
    if ($row['status'] === 'Approved') {
        $mode = 'handover';
    } elseif ($row['status'] === 'Borrowed') {
        $mode = 'return';
    } else {
        $pdo->rollBack();
        fail(409, 'This request is not ready for a QR scan.');
    }

    $dueDate = (new DateTime('+' . LOAN_DAYS . ' days'))->format('Y-m-d');

    // Step 1: preview only. Nothing is changed.
    if (!$confirm) {
        $pdo->rollBack();
        echo json_encode([
            'ok'        => true,
            'preview'   => true,
            'mode'      => $mode,
            'title'     => $row['title'],
            'due_label' => (new DateTime($dueDate))->format('M j, Y'),
        ]);
        exit;
    }

    // Step 2: the owner confirmed, so commit
    if ($mode === 'handover') {
        $pdo->prepare("UPDATE borrow_requests SET status = 'Borrowed', due_date = ? WHERE request_id = ? AND status = 'Approved'")
            ->execute([$dueDate, $requestId]);
        $pdo->prepare("UPDATE books SET availability = 'borrowed' WHERE book_id = ?")
            ->execute([$row['book_id']]);
        $newStatus = 'Borrowed';
        $message   = 'Handover confirmed: "' . $row['title'] . '". Please return it by ' . $dueDate . '.';
    } else {
        $pdo->prepare("UPDATE borrow_requests SET status = 'Returned', returned_at = NOW() WHERE request_id = ? AND status = 'Borrowed'")
            ->execute([$requestId]);
        $pdo->prepare("UPDATE books SET availability = 'available' WHERE book_id = ?")
            ->execute([$row['book_id']]);
        $newStatus = 'Returned';
        $message   = 'Return confirmed: "' . $row['title'] . '". Thank you!';
    }

    $pdo->prepare('INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)')
        ->execute([$row['borrower_id'], $requestId, mb_substr($message, 0, 255)]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'status' => $newStatus]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    fail(500, 'Database error.');
}