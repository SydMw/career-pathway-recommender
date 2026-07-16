<?php require __DIR__ . '/../src/controllers/admin_reset_password_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | <?= htmlspecialchars($student['full_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php $navbar_admin = true; ob_start(); ?>
<a href="admin_student_report.php?id=<?= (int) $student['user_id'] ?>">Back to Student Report</a>
<a href="admin_dashboard.php">Dashboard</a>
<a href="logout.php">Logout</a>
<?php $navbar_links = ob_get_clean(); include __DIR__ . '/partials/navbar.php'; ?>
<div class="container">
    <div class="card auth-card">
        <h2>Reset Password</h2>
        <p class="muted" style="margin-bottom:18px;">
            You are resetting the password for <strong><?= htmlspecialchars($student['full_name']) ?></strong>
            (<?= htmlspecialchars($student['email']) ?>).
            After resetting, share the new password with the student directly.
        </p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="result" style="margin-bottom:16px;">
                <p><?= htmlspecialchars($success) ?></p>
                <p style="margin-top:12px;">
                    <a href="admin_student_report.php?id=<?= (int) $student['user_id'] ?>">Back to student report</a>
                    &nbsp;&nbsp;
                    <a href="admin_dashboard.php">Back to dashboard</a>
                </p>
            </div>
        <?php else: ?>
        <form method="post" action="admin_reset_password.php?id=<?= (int) $student['user_id'] ?>">
            <?= csrf_field() ?>

            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="8">
            <ul class="password-rules" id="pw-rules">
                <li id="r-len">At least 8 characters</li>
                <li id="r-upper">One uppercase letter (A–Z)</li>
                <li id="r-lower">One lowercase letter (a–z)</li>
                <li id="r-num">One number (0–9)</li>
                <li id="r-special">One special character (! @ # $ % etc.)</li>
            </ul>

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">

            <button type="submit" style="margin-top:12px;">Reset Password</button>
            <a href="admin_student_report.php?id=<?= (int) $student['user_id'] ?>"
               style="display:inline-block;margin-left:16px;color:var(--muted);">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
</div>
<script>
const pw = document.getElementById('new_password');
if (pw) {
    const rules = {
        'r-len':     v => v.length >= 8,
        'r-upper':   v => /[A-Z]/.test(v),
        'r-lower':   v => /[a-z]/.test(v),
        'r-num':     v => /[0-9]/.test(v),
        'r-special': v => /[\W_]/.test(v),
    };
    pw.addEventListener('input', function () {
        Object.entries(rules).forEach(([id, fn]) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (fn(this.value)) {
                el.classList.add('rule-pass');
                el.classList.remove('rule-fail');
            } else {
                el.classList.add('rule-fail');
                el.classList.remove('rule-pass');
            }
        });
    });
}
</script>
</body>
</html>
