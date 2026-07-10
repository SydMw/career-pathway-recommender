<?php require __DIR__ . '/../src/controllers/login_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="card auth-card">
        <h1>Career Pathway Recommender</h1>
        <p class="muted">Helping Kenyan students find the right path after secondary school.</p>
        <h2>Welcome back</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="post" action="login.php">
            <?= csrf_field() ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Sign In</button>
        </form>
        <p class="muted">Don't have an account yet? <a href="register.php">Create one here</a></p>
    </div>
</div>
</body>
</html>
