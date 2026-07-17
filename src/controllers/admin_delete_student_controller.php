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

$stmt = $pdo->prepare('SELECT user_id, student_id, full_name, email FROM users WHERE user_id = ? AND role = ?');
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
        // ON DELETE CASCADE on academic_records/recommendations (and
        // feedback, via recommendations) removes everything tied to this
        // student automatically.
        $del = $pdo->prepare('DELETE FROM users WHERE user_id = ? AND role = ?');
        $del->execute([$student_id, 'student']);
        $success = 'Student and all of their records have been permanently deleted.';
    }
}
