<?php
// Administrator Module: per-pathway printable report (FR7)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require_role('admin');

$pathway_name = trim($_GET['pathway'] ?? '');
if ($pathway_name === '') {
    http_response_code(400);
    die('Missing pathway name.');
}

$pathway_stmt = $pdo->prepare('SELECT pathway_id, name, description FROM pathways WHERE name = ?');
$pathway_stmt->execute([$pathway_name]);
$pathway = $pathway_stmt->fetch();

if (!$pathway) {
    http_response_code(404);
    die('Pathway not found.');
}

$recs_stmt = $pdo->prepare(
    'SELECT u.full_name, u.email, r.confidence, r.created_at,
        a.math_score, a.english_score, a.science_score, a.humanities_score,
        a.creative_arts_score, a.interests
     FROM recommendations r
     JOIN users u ON u.user_id = r.user_id
     JOIN academic_records a ON a.record_id = r.record_id
     WHERE r.pathway_id = ? AND u.deleted_at IS NULL
     ORDER BY r.created_at DESC'
);
$recs_stmt->execute([$pathway['pathway_id']]);
$recommendations = $recs_stmt->fetchAll();

$avg_stmt = $pdo->prepare(
    'SELECT
        AVG(a.math_score) AS avg_math, AVG(a.english_score) AS avg_english,
        AVG(a.science_score) AS avg_science, AVG(a.humanities_score) AS avg_humanities,
        AVG(a.creative_arts_score) AS avg_arts, AVG(r.confidence) AS avg_confidence
     FROM recommendations r
     JOIN academic_records a ON a.record_id = r.record_id
     JOIN users u ON u.user_id = r.user_id
     WHERE r.pathway_id = ? AND u.deleted_at IS NULL'
);
$avg_stmt->execute([$pathway['pathway_id']]);
$averages = $avg_stmt->fetch();
