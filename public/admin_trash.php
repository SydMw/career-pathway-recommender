<?php require __DIR__ . '/../src/controllers/admin_trash_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trash | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$navbar_admin = true;
$navbar_links = [
    ['href' => 'admin_dashboard.php', 'text' => 'Dashboard'],
    ['href' => 'logout.php', 'text' => 'Logout'],
];
include __DIR__ . '/partials/navbar.php';
?>
<div class="container">
    <div class="card">
        <h2>Trash</h2>
        <p class="muted" style="margin-bottom:18px;">
            Students moved to trash stay here for 30 days and can be restored at any time before then.
            After 30 days they, and all of their records, are permanently deleted.
        </p>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (empty($trashed)): ?>
            <p class="muted">Trash is empty.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table>
                <tr>
                    <th>Student ID</th><th>Name</th><th>Email</th>
                    <th>Moved to trash</th><th>Days remaining</th><th>Action</th>
                </tr>
                <?php foreach ($trashed as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['student_id'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($t['full_name']) ?></td>
                        <td><?= htmlspecialchars($t['email']) ?></td>
                        <td style="white-space:nowrap;"><?= date('j M Y', strtotime($t['deleted_at'])) ?></td>
                        <td><?= (int) $t['days_left'] ?> day<?= (int) $t['days_left'] === 1 ? '' : 's' ?></td>
                        <td>
                            <form method="post" action="admin_trash.php" style="margin:0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="restore_id" value="<?= (int) $t['user_id'] ?>">
                                <button type="submit" class="btn-secondary" style="margin-top:0;padding:5px 14px;font-size:13px;">Restore</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
