<?php
// Administrator Module: delete a student account (FR7 extension)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/helpers.php';
require_role('admin');

$student_id = (int) ($_GET['id'] ?? 0);
$student = require_student_by_id($pdo, $student_id);

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
        // Permanent delete: ON DELETE CASCADE removes their academic
        // records and recommendations along with the account.
        $del = $pdo->prepare('DELETE FROM users WHERE user_id = ? AND role = ?');
        $del->execute([$student_id, 'student']);
        $success = 'Student and all of their records have been permanently deleted.';
    }
}
