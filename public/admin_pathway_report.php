<?php require __DIR__ . '/../src/controllers/admin_pathway_report_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pathway Report | <?= htmlspecialchars($pathway['name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="navbar no-print">
    <div class="brand">Career Pathway Recommender &nbsp;|&nbsp; Admin</div>
    <div>
        <a href="admin_dashboard.php">Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <div class="card no-print" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <button type="button" onclick="window.history.back()" class="btn-secondary" style="margin-top:0;">&#8592; Close</button>
        <button type="button" onclick="window.print()">Print or Save as PDF</button>
    </div>

    <div id="report-content">
        <h1 class="print-only">Pathway Report: <?= htmlspecialchars($pathway['name']) ?></h1>
        <p class="muted print-only">Generated <?= date('j F Y \a\t H:i') ?> by <?= htmlspecialchars($_SESSION['full_name']) ?></p>

        <div class="card">
            <h2><?= htmlspecialchars($pathway['name']) ?></h2>
            <p><?= htmlspecialchars($pathway['description']) ?></p>
            <p class="muted" style="margin-top:8px;"><?= count($recommendations) ?> student(s) have been recommended this pathway</p>
        </div>

        <?php if (!empty($recommendations)): ?>
        <div class="card">
            <h2>Average Profile of Students on This Pathway</h2>
            <table>
                <tr>
                    <th>Math</th><th>English</th><th>Science</th>
                    <th>Humanities</th><th>Creative Arts</th><th>Confidence</th>
                </tr>
                <tr>
                    <td><?= round($averages['avg_math'], 1) ?></td>
                    <td><?= round($averages['avg_english'], 1) ?></td>
                    <td><?= round($averages['avg_science'], 1) ?></td>
                    <td><?= round($averages['avg_humanities'], 1) ?></td>
                    <td><?= round($averages['avg_arts'], 1) ?></td>
                    <td><?= round($averages['avg_confidence'], 1) ?>%</td>
                </tr>
            </table>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Students Recommended <?= htmlspecialchars($pathway['name']) ?></h2>
            <?php if (empty($recommendations)): ?>
                <p class="muted">No students have been recommended this pathway yet.</p>
            <?php else: ?>
                <div class="table-scroll">
                <table>
                    <tr>
                        <th>Student</th><th>Email</th><th>Confidence</th>
                        <th>Maths</th><th>English</th><th>Science</th><th>Humanities</th><th>Arts</th>
                        <th>Interest</th><th>Date</th>
                    </tr>
                    <?php foreach ($recommendations as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['confidence']) ?>%</td>
                            <td><?= htmlspecialchars($r['math_score']) ?></td>
                            <td><?= htmlspecialchars($r['english_score']) ?></td>
                            <td><?= htmlspecialchars($r['science_score']) ?></td>
                            <td><?= htmlspecialchars($r['humanities_score']) ?></td>
                            <td><?= htmlspecialchars($r['creative_arts_score']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($r['interests'])) ?></td>
                            <td style="white-space:nowrap;"><?= date('j M Y, H:i', strtotime($r['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
