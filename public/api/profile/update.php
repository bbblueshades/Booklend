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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed.');

$in = json_decode(file_get_contents('php://input'), true) ?? [];

$fullName = trim(preg_replace('/\s+/', ' ', (string)($in['full_name'] ?? '')));
$email    = strtolower(trim((string)($in['email'] ?? '')));
$newPw    = (string)($in['new_password'] ?? '');        // passwords are never trimmed
$confirm  = (string)($in['confirm_password'] ?? '');

// ----- Validation -----
if ($fullName === '' || mb_strlen($fullName) > 100)  fail(422, 'Full name is required (max 100 characters).');
if ($email === '' || strlen($email) > 100)           fail(422, 'Email is required (max 100 characters).');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))      fail(422, 'Please enter a valid email address.');

$changePassword = $newPw !== '';
if ($changePassword) {
    if (strlen($newPw) < 8)                                   fail(422, 'Password must be at least 8 characters.');
    if (strlen($newPw) > 72)                                  fail(422, 'Password must be at most 72 characters.');
    if (!preg_match('/[A-Za-z]/', $newPw) || !preg_match('/\d/', $newPw)) {
        fail(422, 'Password needs at least one letter and one number.');
    }
    if ($newPw !== $confirm)                                  fail(422, 'Passwords do not match.');
} elseif ($confirm !== '') {
    fail(422, 'Please enter the new password as well.');
}

$uid = (int)$user['user_id'];

try {
    // Email must be unique (ignoring this user's own row)
    $dup = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND user_id <> ?');
    $dup->execute([$email, $uid]);
    if ($dup->fetch()) fail(409, 'That email is already in use.');

    if ($changePassword) {
        // Rehash only when a new password was given
        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $upd  = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE user_id = ?');
        $upd->execute([$fullName, $email, $hash, $uid]);
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    } else {
        $upd = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?');
        $upd->execute([$fullName, $email, $uid]);
    }

    echo json_encode([
        'ok'        => true,
        'message'   => 'Record successfully saved.',
        'full_name' => $fullName,
        'email'     => $email,
    ]);
} catch (PDOException $e) {
    // 23000 = duplicate key: someone took the email between the check and the update
    if ($e->getCode() === '23000') fail(409, 'That email is already in use.');
    error_log($e->getMessage());
    fail(500, 'Database error.');
}