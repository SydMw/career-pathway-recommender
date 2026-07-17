<?php
const SESSION_TIMEOUT_SECONDS = 600; // log out after 10 minutes of inactivity

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logged-in users are timed out after a period of inactivity; anonymous
// visitors (login/register pages) are untouched since they have no
// last_activity to check yet.
if (!empty($_SESSION['user_id']) && !empty($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['timeout_message'] = 'You were logged out after 10 minutes of inactivity. Please sign in again.';
    header('Location: /career_system/public/login.php');
    exit;
}
$_SESSION['last_activity'] = time();

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /career_system/public/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        die('Forbidden: insufficient privileges.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}
