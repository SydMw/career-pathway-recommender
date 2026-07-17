<?php require __DIR__ . '/../src/controllers/admin_delete_student_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Move to Trash | <?= htmlspecialchars($student['full_name']) ?></title>
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
    <div class="card auth-card">
        <h2>Move Student to Trash</h2>

        <?php if ($success): ?>
            <div class="result" style="margin-bottom:16px;">
                <p><?= htmlspecialchars($success) ?></p>
                <p style="margin-top:12px;">
                    <a href="admin_dashboard.php">Back to dashboard</a>
                    &nbsp;&nbsp;
                    <a href="admin_trash.php">View trash</a>
                </p>
            </div>
        <?php else: ?>
            <p class="muted" style="margin-bottom:18px;">
                You are about to move <strong><?= htmlspecialchars($student['full_name']) ?></strong>
                (<?= htmlspecialchars($student['student_id'] ?? $student['email']) ?>) to the trash,
                along with all <?= $submission_count ?> of their academic record submission<?= $submission_count === 1 ? '' : 's' ?>
                and every recommendation they received. They will be hidden from the roster immediately and
                <strong>can be restored within 30 days</strong> from the trash page — after that, the deletion becomes permanent.
            </p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="admin_delete_student.php?id=<?= (int) $student['user_id'] ?>">
                <?= csrf_field() ?>

                <label for="confirmation">Type <strong><?= htmlspecialchars($student['full_name']) ?></strong> to confirm</label>
                <input type="text" id="confirmation" name="confirmation" required autocomplete="off">

                <button type="submit" class="btn-danger" style="margin-top:12px;">Move to Trash</button>
                <a href="admin_student_report.php?id=<?= (int) $student['user_id'] ?>"
                   style="display:inline-block;margin-left:16px;color:var(--muted);">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
