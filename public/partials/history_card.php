<?php
/**
 * Renders one recommendation-history card per row in $history.
 * Shared by student_dashboard.php and admin_student_report.php —
 * before including this, set $history to an array of rows from a query
 * joining recommendations + pathways + academic_records.
 */
?>
<?php foreach ($history as $h):
    $bc = pathway_badge_class($h['pathway']);
?>
    <div class="result" style="margin-bottom: 16px;">
        <h3>
            <span class="badge <?= $bc ?>"><?= htmlspecialchars($h['pathway']) ?></span>
            &nbsp;<?= htmlspecialchars($h['confidence']) ?>% confidence
        </h3>
        <p class="muted" style="margin-top:6px;">
            <?= date('j F Y \a\t H:i', strtotime($h['created_at'])) ?> &nbsp;&bull;&nbsp;
            Interest: <?= htmlspecialchars(ucfirst($h['interest'])) ?>
        </p>
        <p style="margin-top:8px;"><?= htmlspecialchars($h['explanation']) ?></p>
        <div class="table-scroll">
        <table style="margin-top:12px;">
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
    </div>
<?php endforeach; ?>
