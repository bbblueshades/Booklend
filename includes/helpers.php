<?php
// Escape output to prevent XSS
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

// One-time messages shown after a redirect
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// CSRF protection
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// Builds one navbar item and highlights it when it is the current page
function navLink(string $path, string $label, string $extra = ''): string
{
    $active = ($_SERVER['SCRIPT_NAME'] === BASE_URL . '/' . $path);
    $class  = 'nav-link' . ($active ? ' active' : '');
    $aria   = $active ? ' aria-current="page"' : '';

    return '<li class="nav-item"><a class="' . $class . '"' . $aria
         . ' href="' . e(url($path)) . '">' . e($label) . $extra . '</a></li>';
}

// Number of unread notifications for the notification badge
function unreadCount(int $userId): int
{
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
    );
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// CSRF check for fetch() requests, which send the token in a header
function verifyCsrfHeader(): bool
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// Reads a JSON request body into an array
function jsonInput(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function sendSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // camera=(self) must stay allowed, or QR scanning will be blocked
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
    if (!empty($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

// Cover images may only come from Open Library (no uploads)
function isValidCoverUrl(string $url): bool
{
    $parts = parse_url($url);
    return $parts !== false
        && ($parts['scheme'] ?? '') === 'https'
        && ($parts['host'] ?? '') === 'covers.openlibrary.org'
        && strlen($url) <= 255;
}

// Shared guard for state-changing API endpoints
function requirePostApi(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfHeader()) {
        jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
    }
}