<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id'   => (int) $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
    ];
}

function isAdmin(): bool
{
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Where each role lands after login
function homePathFor(string $role): string
{
    return $role === 'admin' ? 'admin/dashboard.php' : 'browse.php';
}

function loginUser(array $user): void
{
    session_regenerate_id(true); // prevents session fixation
    $_SESSION['user_id']   = (int) $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Call at the top of every protected page. Pass true for API endpoints.
function requireLogin(bool $json = false): void
{
    if (isLoggedIn()) {
        return;
    }
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please log in.']);
        exit;
    }
    setFlash('warning', 'Please log in to continue.');
    redirect('login.php');
}

function requireAdmin(bool $json = false): void
{
    requireLogin($json);
    if (isAdmin()) {
        return;
    }
    http_response_code(403);
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Admins only.']);
        exit;
    }
    setFlash('danger', 'You do not have permission to open that page.');
    redirect('browse.php');
}