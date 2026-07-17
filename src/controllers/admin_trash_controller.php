<?php
// Administrator Module: trash — soft-deleted students, recoverable for
// 30 days before being permanently purged.
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require_role('admin');

// Lazy purge: there's no background job in this system, so anything past
// its 30-day window gets permanently removed the next time this page loads.
// ON DELETE CASCADE cleans up their academic records/recommendations/feedback.
$pdo->exec("DELETE FROM users WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 30 DAY");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    require_csrf();
    $restore_id = (int) $_POST['restore_id'];
    $stmt = $pdo->prepare('UPDATE users SET deleted_at = NULL WHERE user_id = ? AND role = "student" AND deleted_at IS NOT NULL');
    $stmt->execute([$restore_id]);
    $message = $stmt->rowCount() > 0 ? 'Student restored.' : '';
}

$trashed = $pdo->query(
    'SELECT user_id, student_id, full_name, email, deleted_at,
        DATEDIFF(deleted_at + INTERVAL 30 DAY, NOW()) AS days_left
     FROM users
     WHERE role = "student" AND deleted_at IS NOT NULL
     ORDER BY deleted_at DESC'
)->fetchAll();
