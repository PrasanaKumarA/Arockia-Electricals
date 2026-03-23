<?php
// ============================================================
// Arockia Electricals - Auth Middleware
// ============================================================

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session timeout
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        setcookie(SESSION_NAME, '', time() - 3600, '/');
        header('Location: ' . APP_URL . '/auth/login.php?timeout=1');
        exit();
    }
}
$_SESSION['last_activity'] = time();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit();
}
