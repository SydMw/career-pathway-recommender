<?php
function pathway_badge_class(string $pathway): string
{
    return match ($pathway) {
        'STEM' => 'badge-stem',
        'Social Sciences' => 'badge-social',
        default => 'badge-arts',
    };
}

// Generates the next student ID for this year, e.g. STU-2026-0001.
// Counts existing IDs for the year rather than tracking a separate
// counter table — fine at this system's scale.
function generate_student_id(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_id LIKE ?");
    $stmt->execute(["STU-$year-%"]);
    $next = (int) $stmt->fetchColumn() + 1;
    return sprintf('STU-%s-%04d', $year, $next);
}
