<?php
// Single source of truth for the subject score fields and allowed interests,
// shared between validation (student_controller.php) and the form
// (student_dashboard.php) so the two can never drift out of sync.

const SUBJECTS = [
    'math_score' => 'Mathematics score',
    'english_score' => 'English score',
    'science_score' => 'Science score',
    'humanities_score' => 'Humanities score',
    'creative_arts_score' => 'Creative Arts / Sports score',
];

const ALLOWED_INTERESTS = ['technology', 'science', 'business', 'humanities', 'arts', 'sports'];
