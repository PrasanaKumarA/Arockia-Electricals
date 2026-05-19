<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// CSRF protection
$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    jsonResponse(false, 'Invalid security token. Please refresh and try again.');
}

// Role check — only admin can delete
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    jsonResponse(false, 'Permission denied. Admin access required.');
}

$db = getDB();
$id = (int)($_POST['id'] ?? 0);
if (!$id) jsonResponse(false, 'Invalid ID.');

// Soft delete — set status = 0 and record who deleted it
$stmt = $db->prepare("UPDATE sales SET status = 0 WHERE id = ?");
$stmt->execute([$id]);
jsonResponse(true, 'Invoice deleted successfully.');
