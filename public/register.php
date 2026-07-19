<?php require __DIR__ . '/../src/controllers/register_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="card auth-card">
        <h1>Create Your Account</h1>
        <p class="muted">Join the Career Pathway Recommender and discover the best path for you.</p>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="register.php">
            <?= csrf_field() ?>
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="8">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">

            <button type="submit">Register</button>
        </form>
        <p class="muted">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
</div>
</body>
</html>
