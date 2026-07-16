<?php
// Student Module (FR2, FR3, FR4, FR5, FR6)
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/ml_client.php';
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../config/constants.php';
require_role('student');

$error = null;
$result = null;
$feedback_message = null;
$feedback_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_submit'])) {
    // Student rates how good a recommendation actually was — a real signal
    // on recommendation quality, separate from the model's own confidence.
    require_csrf();
    $recommendation_id = (int) ($_POST['recommendation_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $feedback_error = 'Please choose a rating between 1 and 5.';
    } else {
        // Only allow rating a recommendation that actually belongs to this student
        $own_check = $pdo->prepare('SELECT recommendation_id FROM recommendations WHERE recommendation_id = ? AND user_id = ?');
        $own_check->execute([$recommendation_id, $_SESSION['user_id']]);

        if ($own_check->fetch()) {
            $pdo->prepare('INSERT INTO feedback (recommendation_id, rating, comments) VALUES (?, ?, ?)')
                ->execute([$recommendation_id, $rating, $comments !== '' ? $comments : null]);
            $feedback_message = 'Thanks for letting us know what you thought.';
        } else {
            $feedback_error = 'We could not find that recommendation.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $scores = [];
    foreach (array_keys(SUBJECTS) as $f) {
        $val = filter_input(INPUT_POST, $f, FILTER_VALIDATE_FLOAT);
        if ($val === false || $val === null || $val < 0 || $val > 100) {
            $error = 'All scores must be numbers between 0 and 100.';
            break;
        }
        $scores[$f] = $val;
    }
    $interest = strtolower(trim($_POST['interest'] ?? ''));

    if (!$error && !in_array($interest, ALLOWED_INTERESTS, true)) {
        $error = 'Please select a valid interest.';
    }

    if (!$error) {
        // FR6: store submitted academic & interest data
        $stmt = $pdo->prepare(
            'INSERT INTO academic_records
                (user_id, math_score, english_score, science_score, humanities_score, creative_arts_score, interests)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $scores['math_score'],
            $scores['english_score'],
            $scores['science_score'],
            $scores['humanities_score'],
            $scores['creative_arts_score'],
            $interest,
        ]);
        $record_id = (int) $pdo->lastInsertId();

        // FR4: call ML Module API for a prediction
        $ml_payload = array_merge($scores, ['interest' => $interest]);
        $prediction = get_recommendation($ml_payload);

        if (isset($prediction['error'])) {
            error_log('ML service error: ' . $prediction['error']);
            $error = 'The recommendation engine is temporarily unavailable. '
                . 'Your scores were saved, please try again in a moment.';
        } else {
            $pathway_stmt = $pdo->prepare('SELECT pathway_id FROM pathways WHERE name = ?');
            $pathway_stmt->execute([$prediction['pathway']]);
            $pathway_row = $pathway_stmt->fetch();

            if ($pathway_row) {
                // FR6: store the resulting recommendation for future reference
                $insert = $pdo->prepare(
                    'INSERT INTO recommendations
                        (user_id, record_id, pathway_id, confidence, explanation, model_used)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([
                    $_SESSION['user_id'],
                    $record_id,
                    $pathway_row['pathway_id'],
                    $prediction['confidence'],
                    $prediction['explanation'],
                    $prediction['model_used'],
                ]);
                $prediction['recommendation_id'] = (int) $pdo->lastInsertId();
            }

            $result = $prediction; // FR5: displayed with explanation
        }
    }
}

// History for this student, including the marks that produced each recommendation
$history_stmt = $pdo->prepare(
    'SELECT r.confidence, r.explanation, r.created_at, p.name AS pathway,
        a.math_score, a.english_score, a.science_score, a.humanities_score,
        a.creative_arts_score, a.interests
     FROM recommendations r
     JOIN pathways p ON p.pathway_id = r.pathway_id
     JOIN academic_records a ON a.record_id = r.record_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC'
);
$history_stmt->execute([$_SESSION['user_id']]);
$history = $history_stmt->fetchAll();
