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
        <?php if ($feedback_message): ?><div class="success"><?= htmlspecialchars($feedback_message) ?></div><?php endif; ?>
        <?php if ($feedback_error): ?><div class="error"><?= htmlspecialchars($feedback_error) ?></div><?php endif; ?>
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

        <?php if ($result):
            $badge_class = pathway_badge_class($result['pathway']);
        ?>
            <div class="result no-print">
                <h3>
                    Recommended Pathway:
                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($result['pathway']) ?></span>
                </h3>
                <p style="margin-top:10px;"><?= htmlspecialchars($result['explanation']) ?></p>
                <p style="margin-top:12px;font-size:13px;color:var(--muted);">How confident is the system?</p>
                <div class="confidence-bar-wrap">
                    <div class="confidence-bar" id="conf-bar" data-value="<?= (int) $result['confidence'] ?>"></div>
                </div>
                <p><strong><?= htmlspecialchars($result['confidence']) ?>%</strong></p>
                <table style="margin-top:16px;">
                    <tr><th>Pathway</th><th>How well you match</th></tr>
                    <?php foreach ($result['ranking'] as $r): ?>
                        <tr><td><?= htmlspecialchars($r['pathway']) ?></td><td><?= htmlspecialchars($r['score']) ?>%</td></tr>
                    <?php endforeach; ?>
                </table>

                <?php if (!empty($result['recommendation_id'])): ?>
                    <form method="post" action="student_dashboard.php" style="margin-top:18px;border-top:1px solid var(--border);padding-top:16px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="recommendation_id" value="<?= (int) $result['recommendation_id'] ?>">
                        <label for="rating">Was this recommendation helpful?</label>
                        <select id="rating" name="rating" required>
                            <option value="">Rate it from 1 to 5</option>
                            <option value="5">5 — Very helpful</option>
                            <option value="4">4 — Helpful</option>
                            <option value="3">3 — Somewhat helpful</option>
                            <option value="2">2 — Not very helpful</option>
                            <option value="1">1 — Not helpful at all</option>
                        </select>
                        <label for="comments">Anything you'd like to add? (optional)</label>
                        <textarea id="comments" name="comments" rows="2" placeholder="What did you think of this recommendation?"></textarea>
                        <button type="submit" name="feedback_submit" value="1">Send Feedback</button>
                    </form>
                <?php endif; ?>
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
        <h1 class="print-only" style="font-size:20px;margin-bottom:4px;">Career Pathway Recommendation Report</h1>
        <p class="muted print-only" style="margin-bottom:16px;">
            Student: <?= htmlspecialchars($_SESSION['full_name']) ?><br>
            Generated: <?= date('j F Y \a\t H:i') ?>
        </p>
        <h2>Your Recommendation History</h2>
        <?php if (empty($history)): ?>
            <p class="muted">You have not received a recommendation yet. Fill in your scores above and click "Get Recommendation" to get started.</p>
        <?php else: ?>
            <?php foreach ($history as $h):
                $bc = pathway_badge_class($h['pathway']);
            ?>
                <div class="result" style="margin-bottom: 16px;">
                    <h3>
                        <span class="badge <?= $bc ?>"><?= htmlspecialchars($h['pathway']) ?></span>
                        &nbsp;<?= htmlspecialchars($h['confidence']) ?>% confidence
                    </h3>
                    <p class="muted" style="margin-top:6px;"><?= date('j F Y \a\t H:i', strtotime($h['created_at'])) ?> &nbsp;&bull;&nbsp; Interest: <?= htmlspecialchars(ucfirst($h['interest'])) ?></p>
                    <p style="margin-top:8px;"><?= htmlspecialchars($h['explanation']) ?></p>
                    <table>
                        <tr>
                            <th>Mathematics</th><th>English</th><th>Science</th>
                            <th>Humanities</th><th>Creative Arts</th>
                        </tr>
                        <tr>
                            <td><?= htmlspecialchars($h['math_score']) ?></td>
                            <td><?= htmlspecialchars($h['english_score']) ?></td>
                            <td><?= htmlspecialchars($h['science_score']) ?></td>
                            <td><?= htmlspecialchars($h['humanities_score']) ?></td>
                            <td><?= htmlspecialchars($h['creative_arts_score']) ?></td>
                        </tr>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </div>
</div>
</body>
</html>
