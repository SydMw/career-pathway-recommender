<?php require __DIR__ . '/../src/controllers/admin_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Career Pathway Recommender</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$navbar_admin = true;
$navbar_greeting = 'Hi, ' . $_SESSION['full_name'];
$navbar_links = [
    ['href' => 'logout.php', 'text' => 'Logout'],
];
include __DIR__ . '/partials/navbar.php';
?>
<div class="container">
    <div class="card no-print">
        <button type="button" onclick="window.print()">Print or Save Report as PDF</button>
        <p class="muted">This will print the usage statistics, pathway breakdown, student roster, and recent recommendations. The navigation bar and controls will be hidden on the printed page.</p>
    </div>

    <div id="report-content">
    <h1 class="print-only">Career Pathway Recommender; Administrator Report</h1>
    <p class="muted print-only">Generated <?= date('j F Y \a\t H:i') ?> by <?= htmlspecialchars($_SESSION['full_name']) ?></p>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value"><?= (int) $totals['total_students'] ?></div>
            <div class="muted">Students Registered</div>
        </div>
        <div class="stat-box">
            <div class="value"><?= (int) $totals['total_recommendations'] ?></div>
            <div class="muted">Recommendations Given</div>
        </div>
    </div>

    <div class="card no-print">
        <h2>AI Model Retraining</h2>
        <p class="muted">Updates the recommendation model using the latest student submissions alongside the training baseline. This keeps the system accurate as more students use it.</p>
        <?php if ($retrain_error): ?><div class="error"><?= htmlspecialchars($retrain_error) ?></div><?php endif; ?>
        <?php if ($retrain_result): ?>
            <div class="result">
                <h3>Retraining complete</h3>
                <p>Student records used: <?= (int) $retrain_result['total_records_used'] ?>
                   (<?= (int) $retrain_result['real_records_used'] ?> from real students, the rest from the training baseline)</p>
                <p>Model accuracy: <strong><?= round($retrain_result['metrics']['Decision Tree']['accuracy'] * 100, 2) ?>%</strong></p>
            </div>
        <?php endif; ?>
        <form method="post" action="admin_dashboard.php">
            <?= csrf_field() ?>
            <button type="submit" name="retrain" value="1">Update the AI Model Now</button>
        </form>
    </div>

    <div class="card">
        <h2>Recommendations by Pathway</h2>
        <table>
            <tr><th>Pathway</th><th>Total</th><th class="no-print">Report</th></tr>
            <?php foreach ($by_pathway as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['pathway']) ?></td>
                    <td><?= (int) $row['total'] ?></td>
                    <td class="no-print">
                        <a href="admin_pathway_report.php?pathway=<?= urlencode($row['pathway']) ?>">View Report</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <div class="table-toolbar no-print">
            <h2 style="margin-bottom:0;">All Registered Students</h2>
            <div class="toolbar-controls">
                <input type="text" id="student-search" placeholder="Search by student ID, name, or email..." oninput="filterStudents()">
                <select id="student-pathway-filter" onchange="filterStudents()">
                    <option value="">All pathways</option>
                    <option value="STEM">STEM</option>
                    <option value="Social Sciences">Social Sciences</option>
                    <option value="Arts and Sports Science">Arts and Sports Science</option>
                    <option value="—">No submission yet</option>
                </select>
            </div>
        </div>
        <p class="muted no-print" id="student-count"></p>
        <div class="table-scroll">
        <table id="students-table">
            <tr>
                <th>Student ID</th><th>Name</th><th>Email</th><th>Joined</th><th>Submissions</th><th>Latest Pathway</th>
                <th class="no-print">Actions</th>
            </tr>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['student_id'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td style="white-space:nowrap;"><?= date('j M Y', strtotime($s['registered_at'])) ?></td>
                    <td><?= (int) $s['submission_count'] ?></td>
                    <td><?= htmlspecialchars($latest_pathway_by_user[$s['user_id']] ?? 'None yet') ?></td>
                    <td class="no-print" style="white-space:nowrap;">
                        <a href="admin_student_report.php?id=<?= (int) $s['user_id'] ?>">View Report</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <p class="muted no-results" id="no-students" style="display:none;padding:12px 0;">No students match your search.</p>
    </div>

    <div class="card">
        <h2>Recent Recommendations</h2>
        <div class="table-scroll">
        <table id="recs-table">
            <tr>
                <th>Student</th><th>Email</th><th>Pathway</th><th>Confidence</th>
                <th>Maths</th><th>English</th><th>Science</th><th>Humanities</th><th>Arts</th>
                <th>Date</th>
            </tr>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($r['pathway']) ?></span></td>
                    <td><?= htmlspecialchars($r['confidence']) ?>%</td>
                    <td><?= htmlspecialchars($r['math_score']) ?></td>
                    <td><?= htmlspecialchars($r['english_score']) ?></td>
                    <td><?= htmlspecialchars($r['science_score']) ?></td>
                    <td><?= htmlspecialchars($r['humanities_score']) ?></td>
                    <td><?= htmlspecialchars($r['creative_arts_score']) ?></td>
                    <td style="white-space:nowrap;"><?= date('j M Y, H:i', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
    </div>
</div>
<script>
function filterTable(tableId, searchVal, pathwayVal, noResultsId, countId, searchCols, pathwayCol) {
    var rows = document.querySelectorAll('#' + tableId + ' tr:not(:first-child)');
    var visible = 0;
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (!cells.length) return;
        var searchable = '';
        searchCols.forEach(function(col) {
            searchable += (cells[col] ? cells[col].textContent : '') + ' ';
        });
        var pathway = cells[pathwayCol] ? cells[pathwayCol].textContent.trim() : '';
        var matchSearch  = !searchVal  || searchable.toLowerCase().includes(searchVal.toLowerCase());
        var matchPathway = !pathwayVal || pathway.includes(pathwayVal);
        row.style.display = (matchSearch && matchPathway) ? '' : 'none';
        if (matchSearch && matchPathway) visible++;
    });
    document.getElementById(noResultsId).style.display = visible === 0 ? '' : 'none';
    document.getElementById(countId).textContent = visible + ' of ' + rows.length + ' shown';
}

function filterStudents() {
    filterTable(
        'students-table',
        document.getElementById('student-search').value,
        document.getElementById('student-pathway-filter').value,
        'no-students', 'student-count',
        [0, 1, 2], 5
    );
}

filterStudents();
</script>
</body>
</html>
