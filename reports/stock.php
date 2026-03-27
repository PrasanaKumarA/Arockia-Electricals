<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Stock Report';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Reports' => '#', 'Stock Report' => ''];

$category = (int)($_GET['category'] ?? 0);
$filter   = sanitize($_GET['filter'] ?? 'all'); // all, low, out

$where = ["p.status = 1"];
$params = [];
if ($category) { $where[] = "p.category_id = ?"; $params[] = $category; }
if ($filter === 'low')  $where[] = "p.stock_quantity <= p.minimum_stock AND p.stock_quantity > 0";
if ($filter === 'out')  $where[] = "p.stock_quantity = 0";

$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(' AND ', $where) . " ORDER BY p.stock_quantity ASC");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Stock value
$totalStockValue = array_sum(array_map(fn($p) => $p['stock_quantity'] * $p['purchase_price'], $products));
$outOfStock = count(array_filter($products, fn($p) => $p['stock_quantity'] == 0));
$lowStockCount = count(array_filter($products, fn($p) => $p['stock_quantity'] > 0 && $p['stock_quantity'] <= $p['minimum_stock']));

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['SKU', 'Product', 'Category', 'Brand', 'Stock Qty', 'Min Stock', 'Purchase Price', 'Selling Price', 'Stock Value', 'Status']);
    foreach ($products as $p) {
        $status = $p['stock_quantity'] == 0 ? 'Out of Stock' : ($p['stock_quantity'] <= $p['minimum_stock'] ? 'Low Stock' : 'OK');
        fputcsv($out, [$p['sku'] ?? '', $p['product_name'], $p['category_name'] ?? '', $p['brand'] ?? '', $p['stock_quantity'], $p['minimum_stock'], $p['purchase_price'], $p['selling_price'], $p['stock_quantity'] * $p['purchase_price'], $status]);
    }
    fclose($out);
    exit();
}

$categories = getCategories();
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-clipboard-data me-2 text-primary"></i>Stock Report</h1>
        <p class="text-muted mb-0 small">Current inventory stock levels</p>
    </div>
    <a href="?category=<?= $category ?>&filter=<?= $filter ?>&export=csv" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Export CSV</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <div class="btn-group">
                    <a href="?filter=all" class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-outline-secondary' ?>">All</a>
                    <a href="?filter=low" class="btn btn-sm <?= $filter==='low'?'btn-warning':'btn-outline-warning' ?>">Low Stock</a>
                    <a href="?filter=out" class="btn btn-sm <?= $filter==='out'?'btn-danger':'btn-outline-danger' ?>">Out of Stock</a>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card stat-blue"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div class="stat-value"><?= count($products) ?></div><div class="stat-label">Total Products</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-green"><div class="stat-icon"><i class="bi bi-currency-rupee"></i></div><div class="stat-value"><?= formatCurrency($totalStockValue) ?></div><div class="stat-label">Stock Value</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-gold"><div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div><div class="stat-value"><?= $lowStockCount ?></div><div class="stat-label">Low Stock</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-red"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div class="stat-value"><?= $outOfStock ?></div><div class="stat-label">Out of Stock</div></div></div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">Stock Inventory <span class="badge bg-primary ms-1"><?= count($products) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="stockTable">
                <thead><tr><th>#</th><th>SKU</th><th>Product</th><th>Category</th><th>Brand</th><th>In Stock</th><th>Min.</th><th>Cost</th><th>Price</th><th>Stock Value</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $i => $p): ?>
                    <tr class="<?= $p['stock_quantity'] <= $p['minimum_stock'] ? 'low-stock-row' : '' ?>">
                        <td><?= $i+1 ?></td>
                        <td><small><?= sanitize($p['sku'] ?? '—') ?></small></td>
                        <td><strong><?= sanitize($p['product_name']) ?></strong></td>
                        <td><?= sanitize($p['category_name'] ?? '—') ?></td>
                        <td><?= sanitize($p['brand'] ?? '—') ?></td>
                        <td><span class="badge <?= $p['stock_quantity'] == 0 ? 'bg-danger' : ($p['stock_quantity'] <= $p['minimum_stock'] ? 'bg-warning text-dark' : 'bg-success') ?>"><?= $p['stock_quantity'] ?></span></td>
                        <td><?= $p['minimum_stock'] ?></td>
                        <td><?= formatCurrency($p['purchase_price']) ?></td>
                        <td><?= formatCurrency($p['selling_price']) ?></td>
                        <td><?= formatCurrency($p['stock_quantity'] * $p['purchase_price']) ?></td>
                        <td>
                            <?php if ($p['stock_quantity'] == 0): ?><span class="badge bg-danger">Out of Stock</span>
                            <?php elseif ($p['stock_quantity'] <= $p['minimum_stock']): ?><span class="badge bg-warning text-dark">Low</span>
                            <?php else: ?><span class="badge bg-success">OK</span><?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/purchase/add.php?product_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success" title="Restock"><i class="bi bi-cart-plus"></i></a>
                            <a href="<?= APP_URL ?>/products/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraScript = "<script>$(document).ready(function(){ initDataTable('#stockTable', {order:[[5,'asc']], pageLength:25}); });</script>";
include __DIR__ . '/../includes/footer.php';
?>
