<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Never show raw errors to visitors in production
$isProd = !defined('APP_ENV') || APP_ENV === 'production';
ini_set('display_errors', $isProd ? '0' : '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Any uncaught error becomes a friendly message instead of a crash
set_exception_handler(function (Throwable $ex): void {
    error_log((string) $ex);
    http_response_code(500);

    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unable to connect to the server.']);
    } else {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>Something went wrong | Booklend</title>'
           . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
           . '</head><body class="container py-5 text-center"><main>'
           . '<h1>Something went wrong</h1>'
           . '<p>Unable to connect to the server. Please try again in a moment.</p>'
           . '<a class="btn btn-primary" href="' . htmlspecialchars(BASE_URL . '/index.php') . '">Back to home</a>'
           . '</main></body></html>';
    }
    exit;
});

sendSecurityHeaders();

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// Log out after 30 minutes without activity
if (isset($_SESSION['user_id'])) {
    if (time() - ($_SESSION['last_activity'] ?? time()) > 30 * 60) {
        $_SESSION = [];
        session_regenerate_id(true);
        setFlash('warning', 'You were logged out after 30 minutes of inactivity.');
    } else {
        $_SESSION['last_activity'] = time();
    }
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