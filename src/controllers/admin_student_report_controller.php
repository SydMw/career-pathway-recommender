<?php
// Administrator Module: per-student printable report (FR7)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/helpers.php';
require_role('admin');

$student_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$student_id) {
    http_response_code(400);
    die('Missing or invalid student id.');
}

$student_stmt = $pdo->prepare('SELECT user_id, student_id, full_name, email, created_at FROM users WHERE user_id = ? AND role = "student"');
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch();

if (!$student) {
    http_response_code(404);
    die('Student not found.');
}

$history_stmt = $pdo->prepare(
    'SELECT r.confidence, r.explanation, r.created_at, r.model_used, p.name AS pathway,
        a.math_score, a.english_score, a.science_score, a.humanities_score,
        a.creative_arts_score, a.interest
     FROM recommendations r
     JOIN pathways p ON p.pathway_id = r.pathway_id
     JOIN academic_records a ON a.record_id = r.record_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC'
);
$history_stmt->execute([$student_id]);
$history = $history_stmt->fetchAll();
