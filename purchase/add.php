<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Add Purchase';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Purchases' => APP_URL . '/purchase/index.php', 'New Purchase' => ''];

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
        $invoiceNo  = generatePurchaseNumber();
        $paidAmount = $payment_status === 'paid' ? $total : (float)($_POST['paid_amount'] ?? 0);

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO purchases (invoice_number, supplier_id, total_amount, paid_amount, payment_status, payment_method, notes, purchase_date, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$invoiceNo, $supplier_id ?: null, $total, $paidAmount, $payment_status, $payment_method, $notes, $purchase_date, $_SESSION['user_id']]);
            $purchaseId = $db->lastInsertId();

            $itemStmt = $db->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, purchase_price, total_price) VALUES (?,?,?,?,?)");
            foreach ($validItems as $item) {
                $itemStmt->execute([$purchaseId, $item['product_id'], $item['quantity'], $item['purchase_price'], $item['total_price']]);
                updateStock($item['product_id'], $item['quantity'], 'increase');
            }
            $db->commit();
            setFlash('success', "Purchase {$invoiceNo} added. Stock updated.");
            redirect(APP_URL . '/purchase/index.php');
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
        <h1 class="page-title"><i class="bi bi-cart-plus me-2 text-primary"></i>New Purchase</h1>
        <p class="text-muted mb-0 small">Add new stock purchase</p>
    </div>
    <a href="<?= APP_URL ?>/purchase/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<form method="POST" id="purchaseForm">
<div class="row g-3 mb-4">
    <!-- Purchase Info -->
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
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?> — <?= sanitize($s['company'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select" id="paymentStatusSel">
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="paidAmountRow" style="display:none">
                        <label class="form-label">Paid Amount (₹)</label>
                        <div class="input-group"><span class="input-group-text">₹</span>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calculator me-2"></i>Summary</div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="text-muted">Total Items:</span>
                    <strong id="totalItems" class="float-end">0</strong>
                </div>
                <div class="mb-3">
                    <span class="text-muted">Total Qty:</span>
                    <strong id="totalQty" class="float-end">0</strong>
                </div>
                <hr>
                <div class="mb-2">
                    <span class="">Grand Total:</span>
                    <strong class="float-end text-primary fs-5" id="grandTotal">₹0.00</strong>
                </div>
                <button type="submit" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-check-lg me-1"></i>Save Purchase
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Items -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Purchase Items</span>
        <button type="button" class="btn btn-sm btn-primary" onclick="addItem()"><i class="bi bi-plus-lg me-1"></i>Add Item</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="itemsTable">
                <thead>
                    <tr><th>Product</th><th>Quantity</th><th>Purchase Price (₹)</th><th>Total</th><th></th></tr>
                </thead>
                <tbody id="itemsBody">
                    <!-- rows added dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>
</form>

<?php
$productsJson = json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['product_name'], 'price' => $p['purchase_price']], $products));
$extraScript = "
<script>
const allProducts = $productsJson;
let itemCount = 0;

function addItem(pid=0, qty=1, price=0) {
    itemCount++;
    const opts = allProducts.map(p => '<option value=\"'+p.id+'\" data-price=\"'+p.price+'\"'+(p.id==pid?' selected':'')+'>'+p.name+'</option>').join('');
    const row = \`<tr id=\"item-\${itemCount}\">
        <td><select name=\"items[\${itemCount}][product_id]\" class=\"form-select form-select-sm product-select\" onchange=\"onProductChange(this)\" required>
            <option value=\"\">Select Product</option>\${opts}</select></td>
        <td><input type=\"number\" name=\"items[\${itemCount}][quantity]\" class=\"form-control form-control-sm item-qty\" value=\"\${qty}\" min=\"1\" oninput=\"calcRow(this)\" required></td>
        <td><div class=\"input-group input-group-sm\"><span class=\"input-group-text\">₹</span>
            <input type=\"number\" name=\"items[\${itemCount}][purchase_price]\" class=\"form-control item-price\" value=\"\${price}\" step=\"0.01\" min=\"0\" oninput=\"calcRow(this)\" required></div></td>
        <td><span class=\"item-total fw-semibold\">₹0.00</span></td>
        <td><button type=\"button\" onclick=\"removeItem('item-\${itemCount}')\" class=\"btn btn-sm btn-outline-danger\"><i class=\"bi bi-trash\"></i></button></td>
    </tr>\`;
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    const sel = document.querySelector('#item-'+itemCount+' .product-select');
    onProductChange(sel);
    calcTotals();
}
function onProductChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt ? opt.dataset.price : 0;
    const row = sel.closest('tr');
    if (price > 0) row.querySelector('.item-price').value = price;
    calcRow(sel);
}
function calcRow(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value)||0;
    const price = parseFloat(row.querySelector('.item-price').value)||0;
    row.querySelector('.item-total').textContent = '₹' + (qty*price).toFixed(2);
    calcTotals();
}
function removeItem(id) { document.getElementById(id).remove(); calcTotals(); }
function calcTotals() {
    let total=0, qty=0, count=0;
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const q = parseFloat(row.querySelector('.item-qty')?.value)||0;
        const p = parseFloat(row.querySelector('.item-price')?.value)||0;
        total += q*p; qty += q; count++;
    });
    document.getElementById('grandTotal').textContent = '₹'+total.toFixed(2);
    document.getElementById('totalQty').textContent = qty;
    document.getElementById('totalItems').textContent = count;
}
document.getElementById('paymentStatusSel').addEventListener('change', function() {
    document.getElementById('paidAmountRow').style.display = this.value==='partial' ? '' : 'none';
});
addItem();
</script>
";
include __DIR__ . '/../includes/footer.php';
?>
