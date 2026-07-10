<?php
require __DIR__ . '/../src/config/session.php';
session_destroy();
header('Location: /career_system/public/login.php');
exit;
