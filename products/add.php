<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Add Product';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Products' => APP_URL . '/products/index.php', 'Add Product' => ''];

$errors = [];
$categories = getCategories();

// Fetch existing brands for Select2 preload
$brands = $db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

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

    // Check duplicate product name
    $dupCheck = $db->prepare("SELECT id FROM products WHERE product_name = ? AND status = 1");
    $dupCheck->execute([$name]);
    if ($dupCheck->fetch()) $errors[] = "Product '$name' already exists.";

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO products (product_name, category_id, brand, sku, purchase_price, selling_price, stock_quantity, minimum_stock, unit, description) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $category_id ?: null, $brand, $sku ?: null, $purchase_price, $selling_price, $stock_quantity, $minimum_stock, $unit, $description]);

        setFlash('success', "Product '$name' added successfully!");
        redirect(APP_URL . '/products/index.php');
    }
}

// Add Select2 CSS to head
$extraHead = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
';

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
                    <input type="text" name="product_name" id="productNameInput" class="form-control" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required placeholder="Start typing to search or add new...">
                    <div class="form-text">Type a product name. If it exists, it will autocomplete.</div>
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
                    <select name="brand" id="brandSelect" class="form-select">
                        <option value="">Select or type a brand</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>" <?= ($_POST['brand'] ?? '') === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Select existing or type to create new brand.</div>
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

<?php
$appUrl = APP_URL;
$extraScript = "
<script src=\"https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js\"></script>
<script>
$(document).ready(function() {
    // --- Brand Select2: searchable + tags (allows new) ---
    $('#brandSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        tags: true,
        placeholder: 'Select or type a brand',
        allowClear: true,
        createTag: function(params) {
            var term = $.trim(params.term);
            if (term === '') return null;
            return { id: term, text: term, newTag: true };
        },
        templateResult: function(data) {
            if (data.newTag) {
                return $('<span><i class=\"bi bi-plus-circle me-1 text-success\"></i>Create: <strong>' + data.text + '</strong></span>');
            }
            return data.text;
        }
    });

    // --- Product Name autocomplete via Select2 AJAX ---
    var productNameInput = $('#productNameInput');
    var productSelect = $('<select id=\"productNameSelect\" class=\"form-select\"></select>');
    productSelect.insertAfter(productNameInput);
    productNameInput.hide();

    productSelect.select2({
        theme: 'bootstrap-5',
        width: '100%',
        tags: true,
        placeholder: 'Start typing product name...',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '{$appUrl}/products/ajax_lookup.php',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { action: 'search_products', q: params.term };
            },
            processResults: function(data) {
                return { results: data.results.map(function(item) {
                    return { id: item.text, text: item.text };
                })};
            }
        },
        createTag: function(params) {
            var term = $.trim(params.term);
            if (term === '') return null;
            return { id: term, text: term, newTag: true };
        },
        templateResult: function(data) {
            if (data.newTag) {
                return $('<span><i class=\"bi bi-plus-circle me-1 text-success\"></i>New: <strong>' + data.text + '</strong></span>');
            }
            return data.text;
        }
    });

    // Pre-populate if form had a value
    var existingVal = productNameInput.val();
    if (existingVal) {
        var option = new Option(existingVal, existingVal, true, true);
        productSelect.append(option).trigger('change');
    }

    // Sync Select2 value back to hidden input
    productSelect.on('change', function() {
        productNameInput.val($(this).val());
    });
});
</script>
";
include __DIR__ . '/../includes/footer.php';
?>
