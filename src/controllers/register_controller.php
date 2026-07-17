<?php
// Authentication Module - registration (FR1)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/helpers.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = 'Password must contain at least one special character (e.g. ! @ # $).';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $student_id = generate_student_id($pdo);
            $stmt = $pdo->prepare(
                'INSERT INTO users (student_id, full_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$student_id, $full_name, $email, $hash, 'student']);

            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['student_id'] = $student_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role'] = 'student';

            header('Location: /career_system/public/student_dashboard.php');
            exit;
        }
    }
}
