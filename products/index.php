<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Products';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Products' => ''];

// Search & Filter
$search   = sanitize($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);

$where = ["p.status = 1"];
$params = [];
if ($search) { $where[] = "(p.product_name LIKE ? OR p.brand LIKE ? OR p.sku LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
if ($category) { $where[] = "p.category_id = ?"; $params[] = $category; }

$whereClause = implode(' AND ', $where);
$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereClause ORDER BY p.id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = getCategories();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-box-seam me-2 text-primary"></i>Products</h1>
        <p class="text-muted mb-0 small">Manage your product inventory</p>
    </div>
    <a href="<?= APP_URL ?>/products/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Add Product
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Name, brand, SKU..." value="<?= htmlspecialchars($search) ?>">
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
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= APP_URL ?>/products/index.php" class="btn btn-outline-secondary ms-1"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Products <span class="badge bg-primary ms-1"><?= count($products) ?></span></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="productsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $i => $p): ?>
                    <tr id="row-product-<?= $p['id'] ?>" class="<?= $p['stock_quantity'] <= $p['minimum_stock'] ? 'low-stock-row' : '' ?>">
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= sanitize($p['product_name']) ?></strong>
                            <?php if ($p['sku']): ?><br><small class="text-muted"><?= sanitize($p['sku']) ?></small><?php endif; ?>
                        </td>
                        <td><?= sanitize($p['category_name'] ?? '—') ?></td>
                        <td><?= sanitize($p['brand'] ?? '—') ?></td>
                        <td><?= formatCurrency($p['purchase_price']) ?></td>
                        <td><?= formatCurrency($p['selling_price']) ?></td>
                        <td>
                            <span class="badge <?= $p['stock_quantity'] <= $p['minimum_stock'] ? 'stock-badge-low' : 'stock-badge-ok' ?>">
                                <?= $p['stock_quantity'] ?> <?= sanitize($p['unit'] ?? 'pcs') ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['stock_quantity'] == 0): ?>
                                <span class="badge bg-danger">Out of Stock</span>
                            <?php elseif ($p['stock_quantity'] <= $p['minimum_stock']): ?>
                                <span class="badge bg-warning text-dark">Low</span>
                            <?php else: ?>
                                <span class="badge bg-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= APP_URL ?>/products/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button onclick="ajaxDelete('<?= APP_URL ?>/products/delete.php', <?= $p['id'] ?>, '#row-product-<?= $p['id'] ?>')" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraScript = "<script>$(document).ready(function(){ initDataTable('#productsTable', {order:[[0,'asc']]}); });</script>";
include __DIR__ . '/../includes/footer.php';
?>
