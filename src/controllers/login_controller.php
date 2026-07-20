<?php
// Authentication Module - login (FR1: role-based access)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'No account found with that email address. Please <a href="register.php">register</a> first.';
    } elseif (!password_verify($password, $user['password_hash'])) {
        $error = 'Incorrect password. Please try again.';
    } else {
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
