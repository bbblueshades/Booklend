<?php
require_once __DIR__ . '/../../../includes/auth.php';
requireAdmin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfHeader()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
}

$bookId = (int) (jsonInput()['book_id'] ?? 0);
$db = getDB();

$check = $db->prepare(
    "SELECT COUNT(*) FROM borrow_requests
     WHERE book_id = ? AND status IN ('Requested', 'Approved', 'Borrowed')"
);
$check->execute([$bookId]);
if ((int) $check->fetchColumn() > 0) {
    jsonResponse(['success' => false, 'message' => 'Not allowed while a request is active.'], 422);
}

$stmt = $db->prepare('DELETE FROM books WHERE book_id = ?');
$stmt->execute([$bookId]);

jsonResponse(['success' => $stmt->rowCount() > 0,
              'message' => $stmt->rowCount() ? 'Record removed.' : 'No records found.']);