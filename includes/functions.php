<?php
// ============================================================
// Arockia Electricals - Helper Functions
// ============================================================

require_once __DIR__ . '/db.php';

/**
 * Sanitize input
 */
function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function formatCurrency(float $amount): string {
    return APP_CURRENCY . number_format($amount, 2);
}

/**
 * Redirect
 */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate unique invoice number
 */
function generateInvoiceNumber(string $prefix = 'INV'): string {
    $db = getDB();
    $year = date('Y');
    $stmt = $db->prepare("SELECT COUNT(*) FROM sales WHERE YEAR(sale_date) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn() + 1;
    return $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate purchase number
 */
function generatePurchaseNumber(): string {
    $db = getDB();
    $year = date('Y');
    $stmt = $db->prepare("SELECT COUNT(*) FROM purchases WHERE YEAR(purchase_date) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn() + 1;
    return PURCHASE_PREFIX . '-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

/**
 * Get dashboard stats
 */
function getDashboardStats(): array {
    $db = getDB();
    $today = date('Y-m-d');

    // Total products
    $stmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 1");
    $totalProducts = (int)$stmt->fetchColumn();

    // Today's sales
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sale_date = ? AND status = 1");
    $stmt->execute([$today]);
    $todaySales = (float)$stmt->fetchColumn();

    // Total profit (all time)
    $stmt = $db->query("SELECT COALESCE(SUM((si.selling_price - si.purchase_price) * si.quantity), 0) 
                        FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE s.status = 1");
    $totalProfit = (float)$stmt->fetchColumn();

    // Low stock products
    $stmt = $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= minimum_stock AND status = 1");
    $lowStockCount = (int)$stmt->fetchColumn();

    // Total customers
    $stmt = $db->query("SELECT COUNT(*) FROM customers WHERE status = 1");
    $totalCustomers = (int)$stmt->fetchColumn();

    // Monthly sales (last 6 months)
    $stmt = $db->query("SELECT DATE_FORMAT(sale_date, '%b %Y') as month, SUM(total_amount) as total 
                        FROM sales WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND status = 1
                        GROUP BY YEAR(sale_date), MONTH(sale_date) ORDER BY sale_date ASC");
    $monthlySales = $stmt->fetchAll();

    return compact('totalProducts', 'todaySales', 'totalProfit', 'lowStockCount', 'totalCustomers', 'monthlySales');
}

/**
 * Get low stock products
 */
function getLowStockProducts(): array {
    $db = getDB();
    $stmt = $db->query("SELECT p.*, c.name as category_name 
                        FROM products p LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.stock_quantity <= p.minimum_stock AND p.status = 1 
                        ORDER BY p.stock_quantity ASC LIMIT 10");
    return $stmt->fetchAll();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all categories
 */
function getCategories(): array {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Format date
 */
function formatDate(string $date): string {
    return date('d M Y', strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime(string $datetime): string {
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * CSRF token generation
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token verification
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * JSON response helper
 */
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

/**
 * Get product by ID
 */
function getProduct(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Update stock quantity
 */
function updateStock(int $productId, int $quantity, string $type = 'decrease'): bool {
    $db = getDB();
    $operator = $type === 'increase' ? '+' : '-';
    $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity {$operator} ? WHERE id = ?");
    return $stmt->execute([$quantity, $productId]);
}

/**
 * Get whatsapp share link
 */
function getWhatsAppLink(string $invoiceNumber, string $customerName, float $total, string $phone = ''): string {
    $message = "Hello {$customerName}! 🛒\n\n";
    $message .= "*Arockia Electricals*\n";
    $message .= "Invoice: *{$invoiceNumber}*\n";
    $message .= "Total: *" . APP_CURRENCY . number_format($total, 2) . "*\n\n";
    $message .= "Thank you for your purchase! 🙏\n";
    $message .= "For queries: " . COMPANY_PHONE;
    
    $phone = $phone ?: WHATSAPP_NUMBER;
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    return "https://wa.me/{$phone}?text=" . urlencode($message);
}

/**
 * Get all users (admin management)
 */
function getAllUsers(): array {
    $db = getDB();
    $stmt = $db->query("SELECT id, name, email, role, status, last_login, created_at FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Get a single user by ID
 */
function getUserById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
