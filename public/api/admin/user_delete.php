<?php
require_once __DIR__ . '/../../../includes/auth.php';
requireAdmin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfHeader()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
}

$userId = (int) (jsonInput()['user_id'] ?? 0);

if ($userId === currentUser()['user_id']) {
    jsonResponse(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
}

$db = getDB();

// The database would silently cascade-delete the user's books and requests,
// so block deletion while any book is out on loan or approved.
$check = $db->prepare(
    "SELECT COUNT(*) FROM borrow_requests r
     JOIN books b ON b.book_id = r.book_id
     WHERE r.status IN ('Approved', 'Borrowed')
       AND (r.borrower_id = ? OR b.owner_id = ?)"
);
$check->execute([$userId, $userId]);
if ((int) $check->fetchColumn() > 0) {
    jsonResponse(['success' => false,
        'message' => 'Not allowed while this user has an approved or borrowed book.'], 422);
}

$stmt = $db->prepare('DELETE FROM users WHERE user_id = ?');
$stmt->execute([$userId]);

jsonResponse(['success' => $stmt->rowCount() > 0,
              'message' => $stmt->rowCount() ? 'Record removed.' : 'No records found.']);