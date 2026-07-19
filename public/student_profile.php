<?php require __DIR__ . '/../src/controllers/student_profile_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$navbar_greeting = 'Hi, ' . $_SESSION['full_name'];
if (!empty($_SESSION['student_id'])) {
    $navbar_greeting = $navbar_greeting . ' (' . $_SESSION['student_id'] . ')';
}
$navbar_links = [
    ['href' => 'student_dashboard.php', 'text' => 'Dashboard'],
    ['href' => 'logout.php', 'text' => 'Logout'],
];
include __DIR__ . '/partials/navbar.php';
?>
<div class="container">
    <div class="card auth-card">
        <h2>My Profile</h2>
        <p class="muted" style="margin-bottom:18px;">
            Student ID: <strong><?= htmlspecialchars($student['student_id'] ?? '—') ?></strong>
        </p>

        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="post" action="student_profile.php">
            <?= csrf_field() ?>

            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($student['full_name']) ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>

            <hr style="margin:20px 0;border-color:var(--border);">
            <p class="muted" style="margin-bottom:10px;">Only fill these in if you want to change your password.</p>

            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password">

            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" minlength="8">

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8">

            <button type="submit" style="margin-top:12px;">Save Changes</button>
            <a href="student_dashboard.php" style="display:inline-block;margin-left:16px;color:var(--muted);">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
