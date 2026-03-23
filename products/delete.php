<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$db = getDB();
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    jsonResponse(false, 'Invalid product ID.');
}

// Check if product has sales
$stmt = $db->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    // Soft delete only
    $db->prepare("UPDATE products SET status = 0 WHERE id = ?")->execute([$id]);
    jsonResponse(true, 'Product archived (has sales history).');
}

$db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
jsonResponse(true, 'Product deleted successfully.');
