<?php
/**
 * AJAX endpoint for product/brand lookups and dynamic creation.
 * Used by Select2 on Add Product page.
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // --- Search brands (for Select2) ---
    case 'search_brands':
        $q = '%' . ($_GET['q'] ?? '') . '%';
        $stmt = $db->prepare("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' AND brand LIKE ? ORDER BY brand LIMIT 30");
        $stmt->execute([$q]);
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['id' => $row['brand'], 'text' => $row['brand']];
        }
        echo json_encode(['results' => $results]);
        break;

    // --- Search product names (for Select2) ---
    case 'search_products':
        $q = '%' . ($_GET['q'] ?? '') . '%';
        $stmt = $db->prepare("SELECT id, product_name FROM products WHERE product_name LIKE ? AND status = 1 ORDER BY product_name LIMIT 30");
        $stmt->execute([$q]);
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['id' => $row['id'], 'text' => $row['product_name']];
        }
        echo json_encode(['results' => $results]);
        break;

    // --- Create a new brand (returns the brand name) ---
    case 'create_brand':
        $brand = trim($_POST['brand'] ?? '');
        if (empty($brand)) {
            jsonResponse(false, 'Brand name is required.');
        }
        // Brands are stored directly in products table, so just return success
        echo json_encode(['success' => true, 'brand' => $brand]);
        break;

    // --- Quick-create product (minimal fields) ---
    case 'quick_create_product':
        $name = trim($_POST['product_name'] ?? '');
        if (empty($name)) {
            jsonResponse(false, 'Product name is required.');
        }
        // Check for duplicate
        $check = $db->prepare("SELECT id FROM products WHERE product_name = ? AND status = 1");
        $check->execute([$name]);
        if ($existing = $check->fetch()) {
            echo json_encode(['success' => true, 'id' => $existing['id'], 'name' => $name, 'exists' => true]);
            break;
        }
        // Insert minimal product
        $stmt = $db->prepare("INSERT INTO products (product_name, purchase_price, selling_price, stock_quantity) VALUES (?, 0, 0, 0)");
        $stmt->execute([$name]);
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'id' => (int)$newId, 'name' => $name, 'exists' => false]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
