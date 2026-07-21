<?php
// Base path the app is served from, so a redirect only has to change here
// if the project ever moves off http://localhost/career_system/public/.
const APP_URL = '/career_system/public';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/login.php');
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
