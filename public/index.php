<?php
require __DIR__ . '/../src/config/session.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$navbar_links = [
    ['href' => 'login.php', 'text' => 'Login'],
    ['href' => 'register.php', 'text' => 'Register'],
    ['href' => 'mailto:cbc@gmail.com', 'text' => 'Help'],
];
include __DIR__ . '/partials/navbar.php';
?>
<div class="container">
    <div class="card">
        <h1>AI-Powered Career and Pathway Recommendation System</h1>
        <p class="muted">This system helps Kenyan secondary school students choose the right CBC pathway based on their academic performance and personal interests, powered by a hybrid AI recommendation engine.</p>
    </div>

    <div class="card">
        <h2>System Modules</h2>
        <p class="muted">Here is a quick overview of what the system offers. Once you log in, you will only see the features available for your account type.</p>
        <table>
            <tr><th>Module</th><th>What it does</th><th>How to access it</th></tr>
            <tr>
                <td>Account Access</td>
                <td>Create an account, sign in, and access features based on whether you are a student or administrator</td>
                <td><a href="login.php">Sign In</a> &nbsp;|&nbsp; <a href="register.php">Create Account</a></td>
            </tr>
            <tr>
                <td>Student</td>
                <td>Enter your academic scores and interests, then receive a personalised career pathway recommendation with a full explanation</td>
                <td>Register or log in as a student</td>
            </tr>
            <tr>
                <td>Administrator</td>
                <td>Monitor all student activity, view recommendation trends across pathways, and update the AI model with the latest student data</td>
                <td>Log in with an administrator account</td>
            </tr>
            <tr>
                <td>AI Recommendation Engine</td>
                <td>A hybrid AI model combining a Decision Tree and collaborative filtering to match each student to the best pathway for them</td>
                <td>Runs automatically in the background whenever a student submits their information</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Get Started</h2>
        <p>New student? <a href="register.php">Create an account</a> to submit your academic data and get a recommendation.</p>
        <p>Already have an account? <a href="login.php">Log in</a>.</p>
    </div>
</div>
</body>
</html>
<?php exit; ?>
