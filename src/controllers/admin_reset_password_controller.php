<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require_role('admin');

$student_id = (int) ($_GET['id'] ?? 0);
if ($student_id === 0) {
    http_response_code(400);
    die('Missing student ID.');
}

$stmt = $pdo->prepare('SELECT user_id, full_name, email FROM users WHERE user_id = ? AND role = ? AND deleted_at IS NULL');
$stmt->execute([$student_id, 'student']);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    die('Student not found.');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'Password must include at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = 'Password must include at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = 'Password must include at least one number.';
    } elseif (!preg_match('/[\W_]/', $new_password)) {
        $error = 'Password must include at least one special character (e.g. ! @ # $).';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare(
            'UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE user_id = ?'
        );
        $upd->execute([$hashed, $student_id]);
        $success = 'Password reset successfully. Please share the new password with the student securely.';
    }
}
