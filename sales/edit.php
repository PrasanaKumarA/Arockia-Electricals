<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin(); // Only admin can edit invoices

$db = getDB();
$pageTitle = 'Edit Invoice';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Sales' => APP_URL . '/sales/index.php', 'Edit Invoice' => ''];

$saleId = (int)($_GET['id'] ?? 0);
if (!$saleId) redirect(APP_URL . '/sales/index.php');

$sale = $db->prepare("SELECT * FROM sales WHERE id = ?");
$sale->execute([$saleId]);
$saleData = $sale->fetch();
if (!$saleData) redirect(APP_URL . '/sales/index.php');

$saleItemsStmt = $db->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$saleItemsStmt->execute([$saleId]);
$existingItems = $saleItemsStmt->fetchAll();

$customers = $db->query("SELECT * FROM customers WHERE status = 1 ORDER BY name")->fetchAll();
// Fetch products, but don't filter out 0 stock if they are already in this invoice
$products  = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.product_name")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id    = (int)($_POST['customer_id'] ?? 0);
    $sale_date      = sanitize($_POST['sale_date'] ?? $saleData['sale_date']);
    $payment_status = sanitize($_POST['payment_status'] ?? 'paid');
    $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
    $gst_rate       = (float)($_POST['gst_rate'] ?? 0);
    $discount       = (float)($_POST['discount'] ?? 0);
    $notes          = sanitize($_POST['notes'] ?? '');
    $items          = $_POST['items'] ?? [];

    if (empty($items)) $errors[] = 'Please add at least one product.';

    $validItems = [];
    $subtotal = 0;
    
    // Validate stock constraints based on delta
    foreach ($items as $item) {
        $pid   = (int)($item['product_id'] ?? 0);
        $qty   = (int)($item['quantity'] ?? 0);
        $price = (float)($item['selling_price'] ?? 0);
        if ($pid > 0 && $qty > 0 && $price > 0) {
            // Find existing qty for this product in CURRENT invoice
            $existingQty = 0;
            foreach ($existingItems as $ex) {
                if ($ex['product_id'] == $pid) {
                    $existingQty += $ex['quantity'];
                }
            }
            
            $stockStmt = $db->prepare("SELECT stock_quantity, purchase_price, product_name FROM products WHERE id = ?");
            $stockStmt->execute([$pid]);
            $prod = $stockStmt->fetch(); // available stock in DB
            
            $availableNow = $prod['stock_quantity'] + $existingQty; // what we can logically consume
            
            if (!$prod || $availableNow < $qty) {
                $errors[] = "Insufficient stock for " . sanitize($prod['product_name'] ?? "Product #$pid") . " (available max: $availableNow)";
                continue;
            }
            $total = $qty * $price;
            $validItems[] = [
                'product_id'     => $pid,
                'product_name'   => $prod['product_name'],
                'purchase_price' => $prod['purchase_price'],
                'quantity'       => $qty,
                'selling_price'  => $price,
                'total_price'    => $total,
            ];
            $subtotal += $total;
        }
    }

    if (empty($validItems)) $errors[] = 'No valid items provided.';

    if (empty($errors)) {
        $subtotalAfterDiscount = $subtotal - $discount;
        $gstAmount  = ($subtotalAfterDiscount * $gst_rate) / 100;
        $totalAmount = $subtotalAfterDiscount + $gstAmount;
        $paidAmount  = $payment_status === 'paid' ? $totalAmount : (float)($_POST['paid_amount'] ?? 0);

        $db->beginTransaction();
        try {
            // 1. REVERT old stock
            foreach ($existingItems as $ex) {
                updateStock($ex['product_id'], $ex['quantity'], 'increase');
            }
            // 2. DELETE old items
            $db->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$saleId]);
            
            // 3. UPDATE sales record
            $stmt = $db->prepare("UPDATE sales SET customer_id=?, subtotal=?, discount=?, gst_rate=?, gst_amount=?, total_amount=?, paid_amount=?, payment_status=?, payment_method=?, notes=?, sale_date=? WHERE id=?");
            $stmt->execute([$customer_id ?: null, $subtotal, $discount, $gst_rate, $gstAmount, $totalAmount, $paidAmount, $payment_status, $payment_method, $notes, $sale_date, $saleId]);
            
            // 4. INSERT new items & DECREASE stock
            $itemStmt = $db->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, selling_price, purchase_price, total_price) VALUES (?,?,?,?,?,?,?)");
            foreach ($validItems as $item) {
                $itemStmt->execute([$saleId, $item['product_id'], $item['product_name'], $item['quantity'], $item['selling_price'], $item['purchase_price'], $item['total_price']]);
                updateStock($item['product_id'], $item['quantity'], 'decrease');
            }
            
            $db->commit();
            setFlash('success', "Invoice {$saleData['invoice_number']} updated successfully!");
            redirect(APP_URL . '/sales/view.php?id=' . $saleId);
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Invoice</h1>
        <p class="text-muted mb-0 small">Editing standard invoice #<?= sanitize($saleData['invoice_number']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/sales/view.php?id=<?= $saleId ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Invoice</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<form method="POST" id="saleForm">
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Customer & Invoice Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $saleData['customer_id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Invoice Date</label>
                        <input type="date" name="sale_date" class="form-control" value="<?= sanitize($saleData['sale_date']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash" <?= $saleData['payment_method']=='cash'?'selected':'' ?>>Cash</option>
                            <option value="upi" <?= $saleData['payment_method']=='upi'?'selected':'' ?>>UPI</option>
                            <option value="bank_transfer" <?= $saleData['payment_method']=='bank_transfer'?'selected':'' ?>>Bank Transfer</option>
                            <option value="card" <?= $saleData['payment_method']=='card'?'selected':'' ?>>Card</option>
                            <option value="cheque" <?= $saleData['payment_method']=='cheque'?'selected':'' ?>>Cheque</option>
                            <option value="credit" <?= $saleData['payment_method']=='credit'?'selected':'' ?>>Credit</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select" id="salePaymentStatus">
                            <option value="paid" <?= $saleData['payment_status']=='paid'?'selected':'' ?>>Paid</option>
                            <option value="partial" <?= $saleData['payment_status']=='partial'?'selected':'' ?>>Partial</option>
                            <option value="pending" <?= $saleData['payment_status']=='pending'?'selected':'' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="salePaidRow" style="<?= $saleData['payment_status']=='partial' ? '' : 'display:none' ?>">
                        <label class="form-label">Paid Amount (₹)</label>
                        <div class="input-group"><span class="input-group-text">₹</span>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0" value="<?= $saleData['paid_amount'] ?>"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= sanitize($saleData['notes']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Summary -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calculator me-2"></i>Invoice Total</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <strong id="dispSubtotal">₹0.00</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Discount</span>
                    <div class="input-group input-group-sm" style="width:120px">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="discount" id="discountInput" class="form-control" step="0.01" min="0" value="<?= $saleData['discount'] ?>" oninput="calcSaleTotals()">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">GST Rate</span>
                    <select name="gst_rate" id="gstRate" class="form-select form-select-sm" style="width:120px" onchange="calcSaleTotals()">
                        <option value="0" <?= $saleData['gst_rate']==0?'selected':'' ?>>None (0%)</option>
                        <option value="5" <?= $saleData['gst_rate']==5?'selected':'' ?>>5%</option>
                        <option value="12" <?= $saleData['gst_rate']==12?'selected':'' ?>>12%</option>
                        <option value="18" <?= $saleData['gst_rate']==18?'selected':'' ?>>18%</option>
                        <option value="28" <?= $saleData['gst_rate']==28?'selected':'' ?>>28%</option>
                    </select>
                </div>
                <div class="d-flex justify-content-between mb-2" id="gstAmountRow">
                    <span class="text-muted">GST Amount</span>
                    <strong id="dispGst">₹0.00</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold fs-5">Total</span>
                    <strong class="text-primary fs-5" id="dispTotal">₹0.00</strong>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-check-lg me-1"></i>Save Invoice Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Items -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Invoice Items</span>
        <button type="button" class="btn btn-sm btn-primary" onclick="addSaleItem()"><i class="bi bi-plus-lg me-1"></i>Add Item</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Product</th><th>Qty</th><th>Available</th><th>Unit Price (₹)</th><th>Total</th><th></th></tr>
                </thead>
                <tbody id="saleItemsBody"></tbody>
            </table>
        </div>
    </div>
</div>
</form>

<?php
$productsJson = json_encode(array_map(fn($p) => [
    'id' => $p['id'],
    'name' => $p['product_name'],
    'price' => (float)$p['selling_price'],
    'stock' => (int)$p['stock_quantity'],
    'category' => $p['category_name'] ?? ''
], $products));

$existingItemsJson = json_encode(array_map(fn($item) => [
    'pid' => $item['product_id'],
    'qty' => $item['quantity'],
    'price' => $item['selling_price']
], $existingItems));

$extraScript = "
<script>
const saleProducts = $productsJson;
const existingItems = $existingItemsJson;
let saleItemCount = 0;

function addSaleItem(pid=0, qty=1, price=0) {
    saleItemCount++;
    const opts = saleProducts.map(p => '<option value=\"'+p.id+'\" data-price=\"'+p.price+'\" data-stock=\"'+p.stock+'\"'+(p.id==pid?' selected':'')+'>'+p.name+'</option>').join('');
    const tr = document.createElement('tr');
    tr.id = 'saleitem-'+saleItemCount;
    tr.innerHTML = \`
        <td style=\"min-width:220px\"><select name=\"items[\${saleItemCount}][product_id]\" class=\"form-select form-select-sm\" onchange=\"onSaleProductChange(this)\" required>
            <option value=\"\">Select Product</option>\${opts}</select></td>
        <td style=\"width:100px\"><input type=\"number\" name=\"items[\${saleItemCount}][quantity]\" class=\"form-control form-control-sm sale-qty\" value=\"\${qty}\" min=\"1\" oninput=\"calcSaleRow(this)\" required></td>
        <td style=\"width:90px\"><span class=\"badge bg-secondary sale-stock\">—</span></td>
        <td style=\"width:140px\"><div class=\"input-group input-group-sm\"><span class=\"input-group-text\">₹</span>
            <input type=\"number\" name=\"items[\${saleItemCount}][selling_price]\" class=\"form-control sale-price\" value=\"\${price}\" step=\"0.01\" min=\"0\" oninput=\"calcSaleRow(this)\" required></div></td>
        <td><span class=\"sale-total fw-semibold\">₹0.00</span></td>
        <td><button type=\"button\" onclick=\"document.getElementById('saleitem-\${saleItemCount}').remove(); calcSaleTotals();\" class=\"btn btn-sm btn-outline-danger\"><i class=\"bi bi-trash\"></i></button></td>
    \`;
    document.getElementById('saleItemsBody').appendChild(tr);
    const sel = tr.querySelector('select');
    
    // If it's pre-filled, we need to show the original stock + existing item qty as available
    onSaleProductChange(sel, pid, qty);
}

function onSaleProductChange(sel, prePid=0, preQty=0) {
    const opt = sel.options[sel.selectedIndex];
    const row = sel.closest('tr');
    if (opt && opt.value) {
        if(!prePid) row.querySelector('.sale-price').value = opt.dataset.price;
        
        // Logical stock calculation for edit view
        let logicalStock = parseInt(opt.dataset.stock);
        existingItems.forEach(item => {
            if (item.pid == opt.value) {
                logicalStock += parseInt(item.qty); // We can consume what we already owned in this invoice
            }
        });
        
        row.querySelector('.sale-stock').textContent = logicalStock + ' pcs';
        row.querySelector('.sale-stock').className = 'badge ' + (logicalStock > 5 ? 'bg-success' : 'bg-warning text-dark') + ' sale-stock';
    }
    calcSaleRow(sel);
}

function calcSaleRow(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.sale-qty').value)||0;
    const price = parseFloat(row.querySelector('.sale-price').value)||0;
    row.querySelector('.sale-total').textContent = '₹'+(qty*price).toFixed(2);
    calcSaleTotals();
}

function calcSaleTotals() {
    let subtotal = 0;
    document.querySelectorAll('#saleItemsBody tr').forEach(row => {
        const q = parseFloat(row.querySelector('.sale-qty')?.value)||0;
        const p = parseFloat(row.querySelector('.sale-price')?.value)||0;
        subtotal += q*p;
    });
    const discount = parseFloat(document.getElementById('discountInput').value)||0;
    const gstRate = parseFloat(document.getElementById('gstRate').value)||0;
    const afterDiscount = subtotal - discount;
    const gstAmt = afterDiscount * gstRate / 100;
    const total = afterDiscount + gstAmt;
    document.getElementById('dispSubtotal').textContent = '₹'+subtotal.toFixed(2);
    document.getElementById('dispGst').textContent = '₹'+gstAmt.toFixed(2);
    document.getElementById('dispTotal').textContent = '₹'+total.toFixed(2);
}

document.getElementById('salePaymentStatus').addEventListener('change', function() {
    document.getElementById('salePaidRow').style.display = this.value==='partial' ? '' : 'none';
});

// Load existing items
if (existingItems.length > 0) {
    existingItems.forEach(item => {
        addSaleItem(item.pid, item.qty, item.price);
    });
} else {
    addSaleItem();
}
</script>
";
include __DIR__ . '/../includes/footer.php';
?>
