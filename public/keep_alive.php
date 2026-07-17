<?php
// Pinged by the session-timeout banner's "Stay signed in" button.
// Just requiring session.php refreshes last_activity — no separate
// controller needed for a one-line ping.
require __DIR__ . '/../src/config/session.php';
require_login();
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
