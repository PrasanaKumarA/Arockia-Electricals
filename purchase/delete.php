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
requireAdmin();

$db = getDB();
$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    jsonResponse(false, 'Invalid ID.');
}

// Check if purchase exists
$stmt = $db->prepare("SELECT * FROM purchases WHERE id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch();
if (!$purchase) {
    jsonResponse(false, 'Purchase not found.');
}

// Fetch items for stock validation
$itemsStmt = $db->prepare("SELECT pi.*, p.product_name, p.stock_quantity FROM purchase_items pi LEFT JOIN products p ON pi.product_id = p.id WHERE pi.purchase_id = ?");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

// Validate that deleting this purchase doesn't result in negative stock
foreach ($items as $item) {
    if ($item['product_id']) {
        if ($item['stock_quantity'] < $item['quantity']) {
            jsonResponse(false, "Cannot delete purchase: Product '" . sanitize($item['product_name']) . "' stock would drop below 0 (Current stock: {$item['stock_quantity']} pcs, purchased quantity to deduct: {$item['quantity']} pcs).");
        }
    }
}

// Proceed with deletion
$db->beginTransaction();
try {
    foreach ($items as $item) {
        if ($item['product_id']) {
            updateStock($item['product_id'], $item['quantity'], 'decrease');
        }
    }
    
    // Delete purchase (cascades to purchase_items)
    $db->prepare("DELETE FROM purchases WHERE id = ?")->execute([$id]);
    
    $db->commit();
    jsonResponse(true, "Purchase {$purchase['invoice_number']} deleted successfully.");
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
