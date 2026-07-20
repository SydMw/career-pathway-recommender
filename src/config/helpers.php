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

// Looks up a student by their user_id (from ?id= on an admin action page),
// exiting with 400/404 if it's missing or doesn't resolve to a real
// student. Shared by every admin controller that acts on one specific
// student — the caller gets back the fetched row.
function require_student_by_id(PDO $pdo, int $student_id): array
{
    if ($student_id === 0) {
        http_response_code(400);
        die('Missing student ID.');
    }
    $stmt = $pdo->prepare('SELECT user_id, student_id, full_name, email FROM users WHERE user_id = ? AND role = ?');
    $stmt->execute([$student_id, 'student']);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(404);
        die('Student not found.');
    }
    return $student;
}

// Generates the next student ID, e.g. STU001 a single global sequence,
// not scoped to a year. Takes the highest existing number rather than
// counting rows, since permanent deletion (admin_delete_student_controller.php)
// can leave gaps a COUNT(*) based next ID would collide with an
// existing higher one once any student has been deleted.
function generate_student_id(PDO $pdo): string
{
    $stmt = $pdo->prepare(
        "SELECT MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)) FROM users WHERE student_id LIKE 'STU%'"
    );
    $stmt->execute();
    $next = (int) $stmt->fetchColumn() + 1;
    return sprintf('STU%03d', $next);
}
