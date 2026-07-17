<?php
// Authentication Module - login (FR1: role-based access)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';

$error = null;
if (!empty($_SESSION['timeout_message'])) {
    $error = $_SESSION['timeout_message'];
    unset($_SESSION['timeout_message']);
}

const MAX_FAILED_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Lock times are written and read as UTC explicitly (gmdate / '...UTC')
    // so comparisons never depend on PHP's and MySQL's timezone settings
    // agreeing with each other.
    $locked_until_ts = ($user && $user['locked_until']) ? strtotime($user['locked_until'] . ' UTC') : null;

    if ($user && $locked_until_ts && $locked_until_ts > time()) {
        $minutes_left = (int) ceil(($locked_until_ts - time()) / 60);
        $error = "Too many failed attempts. Try again in $minutes_left minute(s).";
    } elseif (!$user) {
        $error = 'No account found with that email address. Please <a href="register.php">register</a> first.';
    } elseif (!password_verify($password, $user['password_hash'])) {
        $attempts = $user['failed_login_attempts'] + 1;
        if ($attempts >= MAX_FAILED_ATTEMPTS) {
            $lock_until = gmdate('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
            $pdo->prepare(
                'UPDATE users SET failed_login_attempts = 0, locked_until = ? WHERE user_id = ?'
            )->execute([$lock_until, $user['user_id']]);
            $error = 'Too many failed attempts. Account locked for ' . LOCKOUT_MINUTES . ' minutes.';
        } else {
            $pdo->prepare('UPDATE users SET failed_login_attempts = ? WHERE user_id = ?')
                ->execute([$attempts, $user['user_id']]);
            $error = 'Incorrect password. Please try again.';
        }
    } else {
        $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE user_id = ?')
            ->execute([$user['user_id']]);

        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header('Location: /career_system/public/admin_dashboard.php');
        } else {
            header('Location: /career_system/public/student_dashboard.php');
        }
        exit;
    }
}
