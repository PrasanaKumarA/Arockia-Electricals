<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$db = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $name    = sanitize($_POST['name'] ?? '');
        if (empty($name)) jsonResponse(false, 'Name is required.');
        $stmt = $db->prepare("INSERT INTO suppliers (name, company, phone, email, gstin, address) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, sanitize($_POST['company'] ?? ''), sanitize($_POST['phone'] ?? ''), sanitize($_POST['email'] ?? ''), sanitize($_POST['gstin'] ?? ''), sanitize($_POST['address'] ?? '')]);
        jsonResponse(true, 'Supplier added successfully.');

    case 'update':
        $id   = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (!$id || empty($name)) jsonResponse(false, 'Invalid data.');
        $stmt = $db->prepare("UPDATE suppliers SET name=?, company=?, phone=?, email=?, gstin=?, address=? WHERE id=?");
        $stmt->execute([$name, sanitize($_POST['company'] ?? ''), sanitize($_POST['phone'] ?? ''), sanitize($_POST['email'] ?? ''), sanitize($_POST['gstin'] ?? ''), sanitize($_POST['address'] ?? ''), $id]);
        jsonResponse(true, 'Supplier updated successfully.');

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'Invalid ID.');
        $db->prepare("UPDATE suppliers SET status = 0 WHERE id = ?")->execute([$id]);
        jsonResponse(true, 'Supplier deleted successfully.');

    default:
        jsonResponse(false, 'Invalid action.');
}
