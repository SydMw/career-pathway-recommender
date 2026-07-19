<?php
function pathway_badge_class(string $pathway): string
{
    return match ($pathway) {
        'STEM' => 'badge-stem',
        'Social Sciences' => 'badge-social',
        default => 'badge-arts',
    };
}

// Checks password strength rules shared by registration, admin reset, and
// profile edit. Returns an error message, or null if the password passes.
function validate_password_strength(string $password, string $label = 'Password'): ?string
{
    if (strlen($password) < 8) {
        return "$label must be at least 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "$label must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "$label must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "$label must contain at least one number.";
    }
    if (!preg_match('/[\W_]/', $password)) {
        return "$label must contain at least one special character (e.g. ! @ # \$).";
    }
    return null;
}

// Generates the next student ID for this year, e.g. STU-2026-0001.
// Takes the highest existing number for the year rather than counting
// rows, since permanent deletion (admin_delete_student_controller.php)
// can leave gaps — a COUNT(*)-based next ID would collide with an
// existing higher one once any student has been deleted.
function generate_student_id(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare(
        "SELECT MAX(CAST(SUBSTRING(student_id, 10) AS UNSIGNED)) FROM users WHERE student_id LIKE ?"
    );
    $stmt->execute(["STU-$year-%"]);
    $next = (int) $stmt->fetchColumn() + 1;
    return sprintf('STU-%s-%04d', $year, $next);
}
