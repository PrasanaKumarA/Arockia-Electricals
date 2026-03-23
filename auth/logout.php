<?php
require_once __DIR__ . '/../includes/config.php';
session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();
header('Location: ' . APP_URL . '/auth/login.php');
exit();
