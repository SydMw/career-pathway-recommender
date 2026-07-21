<?php
require __DIR__ . '/../src/config/session.php';
session_destroy();
header('Location: ' . APP_URL . '/login.php');
exit;
