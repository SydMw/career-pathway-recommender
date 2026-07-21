<?php require __DIR__ . '/../src/controllers/student_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$navbar_greeting = 'Hi, ' . $_SESSION['full_name'];
if (!empty($_SESSION['student_id'])) {
    $navbar_greeting = $navbar_greeting . ' (' . $_SESSION['student_id'] . ')';
}
$navbar_links = [
    ['href' => 'student_profile.php', 'text' => 'My Profile'],
    ['href' => 'logout.php', 'text' => 'Logout'],
];
include __DIR__ . '/partials/navbar.php';
?>
<div class="container">
    <div class="card no-print">
        <h2>Find Your Recommended Pathway</h2>
        <p class="muted">Enter your scores out of 100 for each subject and choose your main area of interest. The system will recommend the best CBC pathway for you.</p>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="student_dashboard.php" id="recommendation-form">
            <?= csrf_field() ?>
            <?php foreach (SUBJECTS as $field => $label): ?>
                <label for="<?= htmlspecialchars($field) ?>"><?= htmlspecialchars($label) ?></label>
                <input type="number" step="0.1" min="0" max="100" id="<?= htmlspecialchars($field) ?>" name="<?= htmlspecialchars($field) ?>" required>
            <?php endforeach; ?>

            <label for="interest">Primary interest</label>
            <select id="interest" name="interest" required>
                <option value="">Select your main interest</option>
                <?php foreach (ALLOWED_INTERESTS as $i): ?>
                    <option value="<?= htmlspecialchars($i) ?>"><?= htmlspecialchars(ucfirst($i)) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" id="submit-btn">Get Recommendation</button>
        </form>
        <script src="assets/js/student.js"></script>

        <?php if ($result): ?>
            <div class="result no-print">
                <h3>
                    Recommended Pathway:
                    <span class="badge"><?= htmlspecialchars($result['pathway']) ?></span>
                </h3>
                <p class="result-explanation"><?= htmlspecialchars($result['explanation']) ?></p>
                <p class="confidence-label">How confident is the system?</p>
                <div class="confidence-bar-wrap">
                    <div class="confidence-bar" id="conf-bar" data-value="<?= (int) $result['confidence'] ?>"></div>
                </div>
                <p><strong><?= htmlspecialchars($result['confidence']) ?>%</strong></p>
                <table>
                    <tr><th>Pathway</th><th>How well you match</th></tr>
                    <?php foreach ($result['ranking'] as $r): ?>
                        <tr><td><?= htmlspecialchars($r['pathway']) ?></td><td><?= htmlspecialchars($r['score']) ?>%</td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <script>
                var bar = document.getElementById('conf-bar');
                if (bar) setTimeout(function(){ bar.style.width = bar.dataset.value + '%'; }, 100);
            </script>
        <?php endif; ?>
    </div>

    <div class="card no-print">
        <button type="button" onclick="window.print()">Print or Save My Report as PDF</button>
        <p class="muted">This will print your full recommendation history, including the explanation for each result.</p>
    </div>

    <div id="report-content">
    <div class="card">
        <h1 class="print-only print-title">Career Pathway Recommendation Report</h1>
        <p class="muted print-only print-header-meta">
            Student: <?= htmlspecialchars($_SESSION['full_name']) ?><br>
            Generated: <?= date('j F Y \a\t H:i') ?>
        </p>
        <h2>Your Recommendation History</h2>
        <?php if (empty($history)): ?>
            <p class="muted">You have not received a recommendation yet. Fill in your scores above and click "Get Recommendation" to get started.</p>
        <?php else: ?>
            <?php include __DIR__ . '/partials/history_card.php'; ?>
        <?php endif; ?>
    </div>
    </div>
</div>
</body>
</html>
