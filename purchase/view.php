<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(APP_URL . '/purchase/index.php'); }

$stmt = $db->prepare("SELECT p.*, s.name as supplier_name, s.company, s.phone as s_phone, s.gstin as s_gstin, s.address as s_address 
                      FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch();
if (!$purchase) { redirect(APP_URL . '/purchase/index.php'); }

$items = $db->prepare("SELECT pi.*, pr.product_name FROM purchase_items pi LEFT JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

$pageTitle = 'Purchase ' . $purchase['invoice_number'];
$breadcrumb = ['Purchases' => APP_URL . '/purchase/index.php', $purchase['invoice_number'] => ''];
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between mb-4">
    <h1 class="page-title"><i class="bi bi-receipt me-2"></i><?= sanitize($purchase['invoice_number']) ?></h1>
    <a href="<?= APP_URL ?>/purchase/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Supplier</h6>
                <p class="mb-0"><strong><?= sanitize($purchase['supplier_name'] ?? 'N/A') ?></strong></p>
                <p class="mb-0 text-muted"><?= sanitize($purchase['company'] ?? '') ?></p>
                <p class="mb-0 text-muted"><?= sanitize($purchase['s_phone'] ?? '') ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <p><strong>Date:</strong> <?= formatDate($purchase['purchase_date']) ?></p>
                <p><strong>Invoice:</strong> <?= sanitize($purchase['invoice_number']) ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge <?= $purchase['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= ucfirst($purchase['payment_status']) ?>
                    </span>
                </p>
            </div>
        </div>
        <table class="table">
            <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= sanitize($item['product_name'] ?? 'Deleted Product') ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= formatCurrency($item['purchase_price']) ?></td>
                    <td><?= formatCurrency($item['total_price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4" class="text-end fw-bold">Total:</td><td class="fw-bold"><?= formatCurrency($purchase['total_amount']) ?></td></tr>
                <tr><td colspan="4" class="text-end">Paid:</td><td><?= formatCurrency($purchase['paid_amount']) ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
