<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
header('Content-Type: application/json');

function fail(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

// Auth + method
$user = currentUser();
if (!$user) fail(401, 'Please log in.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed.');

// Read JSON body
$in = json_decode(file_get_contents('php://input'), true) ?? [];

$title     = trim($in['title'] ?? '');
$author    = trim($in['author'] ?? '');
$isbn      = preg_replace('/[^0-9X]/i', '', $in['isbn'] ?? '');
$cover     = trim($in['cover_url'] ?? '');
$subjectId = (int)($in['subject_id'] ?? 0);
$condition = $in['book_condition'] ?? '';

// Validate everything server-side
if ($title === '' || mb_strlen($title) > 255)   fail(422, 'Title is required (max 255 characters).');
if ($author === '' || mb_strlen($author) > 150) fail(422, 'Author is required (max 150 characters).');
if (!in_array($condition, ['As New', 'Fine', 'Good', 'Poor'], true)) fail(422, 'Invalid condition.');if ($isbn !== '' && !preg_match('/^(\d{9}[\dX]|\d{13})$/i', $isbn)) fail(422, 'Invalid ISBN.');

// Only accept cover URLs from Open Library
if ($cover !== '' && !str_starts_with($cover, 'https://covers.openlibrary.org/')) $cover = '';
if (mb_strlen($cover) > 255) $cover = '';

try {
    $check = $pdo->prepare('SELECT 1 FROM subjects WHERE subject_id = ?');
    $check->execute([$subjectId]);
    if (!$check->fetch()) fail(422, 'Please choose a valid subject.');

    $stmt = $pdo->prepare(
        "INSERT INTO books (owner_id, subject_id, title, author, isbn, book_condition,
                            availability, cover_url, qr_code)
         VALUES (?, ?, ?, ?, ?, ?, 'available', ?, ?)"
    );

    // Unique, unguessable QR token (retry on the rare collision)
    for ($i = 0; $i < 3; $i++) {
        $token = bin2hex(random_bytes(8));      // 16 hex characters
        try {
            $stmt->execute([
                $user['user_id'], $subjectId, $title, $author,
                $isbn === '' ? null : $isbn, $condition,
                $cover === '' ? null : $cover, $token,
            ]);
            http_response_code(201);
            echo json_encode(['book' => [
                'book_id' => (int)$pdo->lastInsertId(),
                'title'   => $title,
                'qr_code' => $token,
            ]]);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;   // 23000 = duplicate key, so retry
        }
    }
    fail(500, 'Could not generate a QR code. Try again.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    fail(500, 'Database error.');
}