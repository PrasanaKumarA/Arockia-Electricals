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

// ============================================================
// Role helpers — call these AFTER auth_check is included
// ============================================================

/**
 * Is the current logged-in user an admin?
 */
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require admin role — redirects staff to dashboard with a flash error.
 * Must be called after auth_check.php is included.
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        // setFlash is in functions.php which may already be included; use session directly if not
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. Admin privileges required.'];
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}
