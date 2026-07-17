<?php
// Administrator Module: delete a student account (FR7 extension)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require_role('admin');

$student_id = (int) ($_GET['id'] ?? 0);
if ($student_id === 0) {
    http_response_code(400);
    die('Missing student ID.');
}

$stmt = $pdo->prepare('SELECT user_id, student_id, full_name, email FROM users WHERE user_id = ? AND role = ? AND deleted_at IS NULL');
$stmt->execute([$student_id, 'student']);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    die('Student not found.');
}

$count_stmt = $pdo->prepare('SELECT COUNT(*) FROM academic_records WHERE user_id = ?');
$count_stmt->execute([$student_id]);
$submission_count = (int) $count_stmt->fetchColumn();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $confirmation = trim($_POST['confirmation'] ?? '');

    if ($confirmation !== $student['full_name']) {
        $error = 'Please type the student\'s full name exactly to confirm deletion.';
    } else {
        // Soft delete: the student and their records stay in the database
        // for 30 days (recoverable from the trash page) before being
        // permanently purged.
        $del = $pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE user_id = ? AND role = ?');
        $del->execute([$student_id, 'student']);
        $success = 'Student moved to trash. They can be restored within 30 days, after which the deletion becomes permanent.';
    }
}
