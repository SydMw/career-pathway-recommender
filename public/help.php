<?php require __DIR__ . '/../src/config/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Help | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$navbar_links = [
    ['href' => 'login.php', 'text' => 'Login'],
    ['href' => 'register.php', 'text' => 'Register'],
    ['href' => 'help.php', 'text' => 'Help'],
];
include __DIR__ . '/partials/navbar.php';
?>
<div class="container">
    <div class="card">
        <h1>Help &amp; Support</h1>
        <p class="muted">Need a hand with the system? Reach out to us using any of the options below.</p>
        <p style="margin-top:16px;">Email: <a href="mailto:cbc@gmail.com">cbc@gmail.com</a></p>
        <p style="margin-top:8px;">Phone: <a href="tel:+254725789632">+254 725 789 632</a></p>
        <p style="margin-top:8px;">For any further inquiries you can visit our physical offices.</p>
    </div>
</div>
</body>
</html>
