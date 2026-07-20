<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/helpers.php';
require_role('admin');

$student_id = (int) ($_GET['id'] ?? 0);
$student = require_student_by_id($pdo, $student_id);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (($pw_error = validate_password_strength($new_password)) !== null) {
        $error = $pw_error;
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $upd->execute([$hashed, $student_id]);
        $success = 'Password reset successfully. Please share the new password with the student securely.';
    }
}
