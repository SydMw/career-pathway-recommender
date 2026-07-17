<?php
// Student Module - profile editing (own name, email, password)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/helpers.php';
require_role('student');

$error = null;
$success = null;

$stmt = $pdo->prepare('SELECT user_id, student_id, full_name, email FROM users WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $wants_password_change = $new_password !== '' || $confirm_password !== '';

    if ($full_name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $dupe = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
        $dupe->execute([$email, $student['user_id']]);
        if ($dupe->fetch()) {
            $error = 'Another account already uses that email address.';
        }
    }

    $new_hash = null;
    if (!$error && $wants_password_change) {
        $row = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $row->execute([$student['user_id']]);
        $current_hash = $row->fetchColumn();

        if ($current_password === '' || !password_verify($current_password, $current_hash)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $new_password)) {
            $error = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $new_password)) {
            $error = 'New password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $new_password)) {
            $error = 'New password must contain at least one number.';
        } elseif (!preg_match('/[\W_]/', $new_password)) {
            $error = 'New password must contain at least one special character (e.g. ! @ # $).';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }

    if (!$error) {
        if ($new_hash !== null) {
            $upd = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE user_id = ?');
            $upd->execute([$full_name, $email, $new_hash, $student['user_id']]);
        } else {
            $upd = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?');
            $upd->execute([$full_name, $email, $student['user_id']]);
        }

        $_SESSION['full_name'] = $full_name;
        $student['full_name'] = $full_name;
        $student['email'] = $email;
        $success = $wants_password_change
            ? 'Your profile and password have been updated.'
            : 'Your profile has been updated.';
    }
}
