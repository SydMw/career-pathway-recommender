<?php require __DIR__ . '/../src/controllers/admin_student_report_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Report | <?= htmlspecialchars($student['full_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$navbar_admin = true;
$navbar_links = [
    ['href' => 'admin_dashboard.php', 'text' => 'Back to Dashboard'],
    ['href' => 'logout.php', 'text' => 'Logout'],
];
include __DIR__ . '/partials/navbar.php';
?>
<div class="container">
    <div class="card no-print" style="position:relative;">
        <button onclick="window.history.back()" class="close-btn no-print" title="Go back">&times;</button>
        <button type="button" onclick="window.print()">Print or Save as PDF</button>
        <a href="admin_reset_password.php?id=<?= (int) $student['user_id'] ?>"
           class="btn-secondary">Reset Student Password</a>
        <a href="admin_delete_student.php?id=<?= (int) $student['user_id'] ?>"
           class="btn-danger">Delete Student</a>
    </div>

    <div id="report-content">
        <h1 class="print-only">Student Report</h1>
        <p class="muted print-only">Generated <?= date('j F Y \a\t H:i') ?> by <?= htmlspecialchars($_SESSION['full_name']) ?></p>

        <div class="card">
            <h2><?= htmlspecialchars($student['full_name']) ?></h2>
            <table>
                <tr><th>Student ID</th><td><?= htmlspecialchars($student['student_id'] ?? '—') ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($student['email']) ?></td></tr>
                <tr><th>Member since</th><td><?= date('j F Y', strtotime($student['created_at'])) ?></td></tr>
                <tr><th>Total Submissions</th><td><?= count($history) ?></td></tr>
            </table>
        </div>

        <div class="card">
            <h2>Recommendation History</h2>
            <?php if (empty($history)): ?>
                <p class="muted">This student has not submitted any academic data yet.</p>
            <?php else: ?>
                <?php include __DIR__ . '/partials/history_card.php'; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
