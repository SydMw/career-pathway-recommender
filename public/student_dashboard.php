<?php require __DIR__ . '/../src/controllers/student_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="navbar no-print">
    <div class="brand">Career Pathway Recommender</div>
    <div>
        <span>Hi, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <div class="card no-print">
        <h2>Find Your Recommended Pathway</h2>
        <p class="muted">Enter your scores out of 100 for each subject and choose your main area of interest. The system will recommend the best CBC pathway for you.</p>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="student_dashboard.php" id="recommendation-form">
            <?= csrf_field() ?>
            <div class="form-row">
                <div>
                    <label for="math_score">Mathematics score</label>
                    <input type="number" step="0.1" min="0" max="100" id="math_score" name="math_score" required>
                </div>
                <div>
                    <label for="english_score">English score</label>
                    <input type="number" step="0.1" min="0" max="100" id="english_score" name="english_score" required>
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label for="science_score">Science score</label>
                    <input type="number" step="0.1" min="0" max="100" id="science_score" name="science_score" required>
                </div>
                <div>
                    <label for="humanities_score">Humanities score</label>
                    <input type="number" step="0.1" min="0" max="100" id="humanities_score" name="humanities_score" required>
                </div>
            </div>
            <label for="creative_arts_score">Creative Arts / Sports score</label>
            <input type="number" step="0.1" min="0" max="100" id="creative_arts_score" name="creative_arts_score" required>

            <label for="interest">Primary interest</label>
            <select id="interest" name="interest" required>
                <option value="">Select your main interest</option>
                <option value="technology">Technology</option>
                <option value="science">Science</option>
                <option value="business">Business</option>
                <option value="humanities">Humanities</option>
                <option value="arts">Arts</option>
                <option value="sports">Sports</option>
            </select>

            <button type="submit" id="submit-btn">Get Recommendation</button>
        </form>
        <script src="assets/js/student.js"></script>

        <?php if ($result):
            $badge_class = match($result['pathway']) {
                'STEM'                   => 'badge-stem',
                'Social Sciences'        => 'badge-social',
                default                  => 'badge-arts',
            };
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
                $bc = match($h['pathway']) {
                    'STEM'            => 'badge-stem',
                    'Social Sciences' => 'badge-social',
                    default           => 'badge-arts',
                };
            ?>
                <div class="result" style="margin-bottom: 16px;">
                    <h3>
                        <span class="badge <?= $bc ?>"><?= htmlspecialchars($h['pathway']) ?></span>
                        &nbsp;<?= htmlspecialchars($h['confidence']) ?>% confidence
                    </h3>
                    <p class="muted" style="margin-top:6px;"><?= date('j F Y \a\t H:i', strtotime($h['created_at'])) ?> &nbsp;&bull;&nbsp; Interest: <?= htmlspecialchars(ucfirst($h['interests'])) ?></p>
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
