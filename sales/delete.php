<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$db = getDB();
$id = (int)($_POST['id'] ?? 0);
if (!$id) jsonResponse(false, 'Invalid ID.');

$db->prepare("UPDATE sales SET status = 0 WHERE id = ?")->execute([$id]);
jsonResponse(true, 'Invoice deleted successfully.');
