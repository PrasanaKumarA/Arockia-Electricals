<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Add Product';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Products' => APP_URL . '/products/index.php', 'Add Product' => ''];

$errors = [];
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = sanitize($_POST['product_name'] ?? '');
    $category_id   = (int)($_POST['category_id'] ?? 0);
    $brand         = sanitize($_POST['brand'] ?? '');
    $sku           = sanitize($_POST['sku'] ?? '');
    $purchase_price = (float)($_POST['purchase_price'] ?? 0);
    $selling_price  = (float)($_POST['selling_price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $minimum_stock  = (int)($_POST['minimum_stock'] ?? 5);
    $unit           = sanitize($_POST['unit'] ?? 'pcs');
    $description    = sanitize($_POST['description'] ?? '');

    if (empty($name))            $errors[] = 'Product name is required.';
    if ($purchase_price <= 0)    $errors[] = 'Valid purchase price is required.';
    if ($selling_price <= 0)     $errors[] = 'Valid selling price is required.';
    if ($stock_quantity < 0)     $errors[] = 'Stock quantity cannot be negative.';

    // Check SKU uniqueness
    if ($sku) {
        $check = $db->prepare("SELECT id FROM products WHERE sku = ?");
        $check->execute([$sku]);
        if ($check->fetch()) $errors[] = 'SKU already exists.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO products (product_name, category_id, brand, sku, purchase_price, selling_price, stock_quantity, minimum_stock, unit, description) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $category_id ?: null, $brand, $sku ?: null, $purchase_price, $selling_price, $stock_quantity, $minimum_stock, $unit, $description]);

        setFlash('success', "Product '$name' added successfully!");
        redirect(APP_URL . '/products/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Product</h1>
        <p class="text-muted mb-0 small">Add a new product to inventory</p>
    </div>
    <a href="<?= APP_URL ?>/products/index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Product Information</div>
    <div class="card-body">
        <form method="POST" novalidate>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">SKU / Code</label>
                    <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>" placeholder="e.g. UPS-APC-600">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" placeholder="e.g. APC, Exide">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-select">
                        <?php foreach (['pcs','set','box','kg','meter','roll','pair'] as $u): ?>
                            <option value="<?= $u ?>" <?= ($_POST['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Price (₹) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($_POST['purchase_price'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selling Price (₹) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="selling_price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($_POST['selling_price'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Opening Stock Qty</label>
                    <input type="number" name="stock_quantity" class="form-control" min="0" value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '0') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum Stock Alert</label>
                    <input type="number" name="minimum_stock" class="form-control" min="0" value="<?= htmlspecialchars($_POST['minimum_stock'] ?? '5') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Product description..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Product</button>
                    <a href="<?= APP_URL ?>/products/index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
