<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin(); // Only admin can edit purchases

$db = getDB();
$pageTitle = 'Edit Purchase';
$purchaseId = (int)($_GET['id'] ?? 0);
if (!$purchaseId) redirect(APP_URL . '/purchase/index.php');

// Fetch purchase
$purchaseStmt = $db->prepare("SELECT * FROM purchases WHERE id = ?");
$purchaseStmt->execute([$purchaseId]);
$purchaseData = $purchaseStmt->fetch();
if (!$purchaseData) redirect(APP_URL . '/purchase/index.php');

$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Purchases' => APP_URL . '/purchase/index.php', 'Edit ' . $purchaseData['invoice_number'] => ''];

// Fetch items
$purchaseItemsStmt = $db->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
$purchaseItemsStmt->execute([$purchaseId]);
$existingItems = $purchaseItemsStmt->fetchAll();

// Fetch suppliers and products
$suppliers = $db->query("SELECT * FROM suppliers WHERE status = 1 ORDER BY name")->fetchAll();
$products  = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.product_name")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id    = (int)($_POST['supplier_id'] ?? 0);
    $purchase_date  = sanitize($_POST['purchase_date'] ?? date('Y-m-d'));
    $payment_status = sanitize($_POST['payment_status'] ?? 'paid');
    $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
    $notes          = sanitize($_POST['notes'] ?? '');
    $items          = $_POST['items'] ?? [];

    if (empty($items)) $errors[] = 'Please add at least one product.';

    $total = 0;
    $validItems = [];
    foreach ($items as $item) {
        $pid   = (int)($item['product_id'] ?? 0);
        $qty   = (int)($item['quantity'] ?? 0);
        $price = (float)($item['purchase_price'] ?? 0);
        if ($pid > 0 && $qty > 0 && $price > 0) {
            $validItems[] = ['product_id' => $pid, 'quantity' => $qty, 'purchase_price' => $price, 'total_price' => $qty * $price];
            $total += $qty * $price;
        }
    }
    if (empty($validItems)) $errors[] = 'Please add valid items with quantity and price.';

    if (empty($errors)) {
        // Stock safety validation
        // Build map of existing item quantities
        $existingQtyMap = [];
        foreach ($existingItems as $ex) {
            $pid = (int)$ex['product_id'];
            if ($pid > 0) {
                $existingQtyMap[$pid] = ($existingQtyMap[$pid] ?? 0) + (int)$ex['quantity'];
            }
        }

        // Build map of new item quantities
        $newQtyMap = [];
        foreach ($validItems as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['quantity'];
            $newQtyMap[$pid] = ($newQtyMap[$pid] ?? 0) + $qty;
        }

        // Gather all products involved in old and new item lists
        $allProductIds = array_unique(array_merge(array_keys($existingQtyMap), array_keys($newQtyMap)));

        foreach ($allProductIds as $pid) {
            $oldQty = $existingQtyMap[$pid] ?? 0;
            $newQty = $newQtyMap[$pid] ?? 0;
            $netChange = $newQty - $oldQty;

            if ($netChange < 0) {
                // We are reducing the purchased quantity, which means decreasing the stock.
                // Verify that we have enough stock in DB.
                $prod = getProduct($pid);
                $currentStock = $prod ? (int)$prod['stock_quantity'] : 0;
                if ($currentStock + $netChange < 0) {
                    $errors[] = "Insufficient stock remaining to decrease the purchased quantity of " . sanitize($prod['product_name'] ?? "Product #$pid") . " (current stock: $currentStock pcs, net deduction: " . abs($netChange) . " pcs).";
                }
            }
        }
    }

    if (empty($errors)) {
        $paidAmount = $payment_status === 'paid' ? $total : ($payment_status === 'pending' ? 0 : (float)($_POST['paid_amount'] ?? 0));

        $db->beginTransaction();
        try {
            // 1. REVERT old stock (decrease stock by old purchased quantities)
            foreach ($existingItems as $ex) {
                if ($ex['product_id']) {
                    updateStock($ex['product_id'], $ex['quantity'], 'decrease');
                }
            }

            // 2. DELETE old items
            $db->prepare("DELETE FROM purchase_items WHERE purchase_id = ?")->execute([$purchaseId]);

            // 3. UPDATE purchases record
            $stmt = $db->prepare("UPDATE purchases SET supplier_id = ?, total_amount = ?, paid_amount = ?, payment_status = ?, payment_method = ?, notes = ?, purchase_date = ? WHERE id = ?");
            $stmt->execute([$supplier_id ?: null, $total, $paidAmount, $payment_status, $payment_method, $notes, $purchase_date, $purchaseId]);

            // 4. INSERT new items & INCREASE stock
            $itemStmt = $db->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, purchase_price, total_price) VALUES (?,?,?,?,?)");
            foreach ($validItems as $item) {
                $itemStmt->execute([$purchaseId, $item['product_id'], $item['quantity'], $item['purchase_price'], $item['total_price']]);
                updateStock($item['product_id'], $item['quantity'], 'increase');
            }

            $db->commit();
            setFlash('success', "Purchase {$purchaseData['invoice_number']} updated successfully. Stock adjusted.");
            redirect(APP_URL . '/purchase/view.php?id=' . $purchaseId);
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Purchase</h1>
        <p class="text-muted mb-0 small">Editing purchase invoice #<?= sanitize($purchaseData['invoice_number']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/purchase/view.php?id=<?= $purchaseId ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchase</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<form method="POST" id="purchaseForm">

<!-- 1. PURCHASE ITEMS (FIRST) -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Purchase Items</span>
        <button type="button" class="btn btn-sm btn-primary" id="addPurchaseItemBtn"><i class="bi bi-plus-lg me-1"></i>Add Item</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="purchaseItemsTable">
                <thead>
                    <tr>
                        <th style="min-width:200px">Product</th>
                        <th style="width:100px">Quantity</th>
                        <th style="width:150px">Purchase Price (₹)</th>
                        <th style="width:110px">Total</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>
        <div class="p-3 text-center text-muted small" id="noItemsMsg" style="display:none">
            <i class="bi bi-info-circle me-1"></i>Click "Add Item" to start adding products
        </div>
    </div>
</div>

<!-- 2. PURCHASE DETAILS + SUPPLIER -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Purchase Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $s['id'] == $purchaseData['supplier_id'] ? 'selected' : '' ?>>
                                    <?= sanitize($s['name']) ?> — <?= sanitize($s['company'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= sanitize($purchaseData['purchase_date']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select" id="paymentStatusSel">
                            <option value="paid" <?= $purchaseData['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="partial" <?= $purchaseData['payment_status'] === 'partial' ? 'selected' : '' ?>>Partial</option>
                            <option value="pending" <?= $purchaseData['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash" <?= $purchaseData['payment_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="upi" <?= $purchaseData['payment_method'] === 'upi' ? 'selected' : '' ?>>UPI</option>
                            <option value="bank_transfer" <?= $purchaseData['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            <option value="cheque" <?= $purchaseData['payment_method'] === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="paidAmountRow" style="<?= $purchaseData['payment_status'] === 'partial' ? '' : 'display:none' ?>">
                        <label class="form-label">Paid Amount (₹)</label>
                        <div class="input-group"><span class="input-group-text">₹</span>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0" value="<?= $purchaseData['paid_amount'] ?>"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."><?= sanitize($purchaseData['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. SUMMARY -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calculator me-2"></i>Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Total Items:</span>
                    <strong id="totalItems">0</strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Total Qty:</span>
                    <strong id="totalQty">0</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Grand Total:</span>
                    <strong class="text-primary fs-5" id="grandTotal">₹0.00</strong>
                </div>
                <button type="submit" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-check-lg me-1"></i>Save Purchase Changes
                </button>
            </div>
        </div>
    </div>
</div>

</form>

<?php
$productsJson = json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['product_name'], 'price' => $p['purchase_price']], $products));
$existingItemsJson = json_encode(array_map(fn($item) => [
    'pid' => $item['product_id'],
    'qty' => $item['quantity'],
    'price' => $item['purchase_price']
], $existingItems));

$extraScript = "
<script>
var allProducts = $productsJson;
var existingItems = $existingItemsJson;
var itemCount = 0;

document.getElementById('addPurchaseItemBtn').addEventListener('click', function() { addItem(); });

function addItem(pid, qty, price) {
    pid = pid || 0;
    qty = qty || 1;
    price = price || 0;
    itemCount++;
    document.getElementById('noItemsMsg').style.display = 'none';
    var opts = '<option value=\"\">Select Product</option>';
    allProducts.forEach(function(p) {
        opts += '<option value=\"'+p.id+'\" data-price=\"'+p.price+'\"'+(p.id==pid?' selected':'')+'>'+p.name+'</option>';
    });
    var row = '<tr id=\"item-'+itemCount+'\">' +
        '<td><select name=\"items['+itemCount+'][product_id]\" class=\"form-select form-select-sm product-select\" onchange=\"onProductChange(this)\" required>'+opts+'</select></td>' +
        '<td><input type=\"number\" name=\"items['+itemCount+'][quantity]\" class=\"form-control form-control-sm item-qty\" value=\"'+qty+'\" min=\"1\" oninput=\"calcRow(this)\" required></td>' +
        '<td><div class=\"input-group input-group-sm\"><span class=\"input-group-text\">₹</span><input type=\"number\" name=\"items['+itemCount+'][purchase_price]\" class=\"form-control item-price\" value=\"'+price+'\" step=\"0.01\" min=\"0\" oninput=\"calcRow(this)\" required></div></td>' +
        '<td><span class=\"item-total fw-semibold\">₹0.00</span></td>' +
        '<td><button type=\"button\" onclick=\"removeItem('+itemCount+')\" class=\"btn btn-sm btn-outline-danger\"><i class=\"bi bi-trash\"></i></button></td>' +
    '</tr>';
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    var sel = document.querySelector('#item-'+itemCount+' .product-select');
    if (sel && pid) onProductChange(sel);
    calcTotals();
}

function onProductChange(sel) {
    var opt = sel.options[sel.selectedIndex];
    var price = opt ? opt.dataset.price : 0;
    var row = sel.closest('tr');
    // Pre-filled existing items should keep their original prices unless manual change
    var currentVal = row.querySelector('.item-price').value;
    if (price > 0 && (!currentVal || parseFloat(currentVal) === 0)) {
        row.querySelector('.item-price').value = price;
    }
    calcRow(sel);
}

function calcRow(el) {
    var row = el.closest('tr');
    var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    var price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-total').textContent = '₹' + (qty * price).toFixed(2);
    calcTotals();
}

function removeItem(n) {
    var el = document.getElementById('item-'+n);
    if (el) el.remove();
    calcTotals();
    if (document.querySelectorAll('#itemsBody tr').length === 0) {
        document.getElementById('noItemsMsg').style.display = '';
    }
}

function calcTotals() {
    var total = 0, qty = 0, count = 0;
    document.querySelectorAll('#itemsBody tr').forEach(function(row) {
        var q = parseFloat(row.querySelector('.item-qty').value) || 0;
        var p = parseFloat(row.querySelector('.item-price').value) || 0;
        total += q * p;
        qty += q;
        count++;
    });
    document.getElementById('grandTotal').textContent = '₹' + total.toFixed(2);
    document.getElementById('totalQty').textContent = qty;
    document.getElementById('totalItems').textContent = count;
}

document.getElementById('paymentStatusSel').addEventListener('change', function() {
    document.getElementById('paidAmountRow').style.display = this.value === 'partial' ? '' : 'none';
});

// Load existing items
if (existingItems.length > 0) {
    existingItems.forEach(function(item) {
        addItem(item.pid, item.qty, item.price);
    });
} else {
    addItem();
}
</script>
";
include __DIR__ . '/../includes/footer.php';
?>
