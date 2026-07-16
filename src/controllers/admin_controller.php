<?php
// Administrator Module (FR7: reports; FR8: trigger model retraining)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/ml_client.php';
require __DIR__ . '/../config/helpers.php';
require_role('admin');

$retrain_result = null;
$retrain_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retrain'])) {
    require_csrf();
    $response = trigger_retrain();
    if (isset($response['error'])) {
        $retrain_error = $response['error'];
    } else {
        $retrain_result = $response;
    }
}

$totals = $pdo->query(
    'SELECT
        (SELECT COUNT(*) FROM users WHERE role = "student") AS total_students,
        (SELECT COUNT(*) FROM recommendations) AS total_recommendations'
)->fetch();

$by_pathway = $pdo->query(
    'SELECT p.name AS pathway, COUNT(*) AS total
     FROM recommendations r
     JOIN pathways p ON p.pathway_id = r.pathway_id
     GROUP BY p.name
     ORDER BY total DESC'
)->fetchAll();

$recent = $pdo->query(
    'SELECT u.full_name, u.email, p.name AS pathway, r.confidence, r.created_at,
        a.math_score, a.english_score, a.science_score, a.humanities_score, a.creative_arts_score
     FROM recommendations r
     JOIN users u ON u.user_id = r.user_id
     JOIN pathways p ON p.pathway_id = r.pathway_id
     JOIN academic_records a ON a.record_id = r.record_id
     ORDER BY r.created_at DESC
     LIMIT 50'
)->fetchAll();

// Full registered-student roster, including students who have not yet
// submitted any data (FR7: administrator visibility into all students).
$students = $pdo->query(
    'SELECT
        u.user_id, u.full_name, u.email, u.created_at AS registered_at,
        COUNT(r.recommendation_id) AS submission_count,
        MAX(r.created_at) AS latest_submission_at
     FROM users u
     LEFT JOIN recommendations r ON r.user_id = u.user_id
     WHERE u.role = "student"
     GROUP BY u.user_id, u.full_name, u.email, u.created_at
     ORDER BY u.created_at DESC'
)->fetchAll();

// Map each student to their most recent pathway (simple PHP-side lookup,
// avoids a fragile correlated subquery for "latest row per group").
$latest_pathway_by_user = [];
$latest_rows = $pdo->query(
    'SELECT r.user_id, p.name AS pathway, r.created_at
     FROM recommendations r
     JOIN pathways p ON p.pathway_id = r.pathway_id
     ORDER BY r.created_at DESC'
)->fetchAll();
foreach ($latest_rows as $row) {
    if (!isset($latest_pathway_by_user[$row['user_id']])) {
        $latest_pathway_by_user[$row['user_id']] = $row['pathway'];
    }
}
