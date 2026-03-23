<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Purchases';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Purchases' => ''];

$stmt = $db->query("SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.id DESC");
$purchases = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-cart-plus me-2 text-primary"></i>Purchases</h1>
        <p class="text-muted mb-0 small">Purchase history and stock intake</p>
    </div>
    <a href="<?= APP_URL ?>/purchase/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Purchase
    </a>
</div>

<div class="card">
    <div class="card-header">Purchase History <span class="badge bg-primary ms-1"><?= count($purchases) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="purchasesTable">
                <thead>
                    <tr><th>#</th><th>Invoice No.</th><th>Supplier</th><th>Date</th><th>Total</th><th>Paid</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($p['invoice_number']) ?></strong></td>
                        <td><?= sanitize($p['supplier_name'] ?? '—') ?></td>
                        <td><?= formatDate($p['purchase_date']) ?></td>
                        <td><?= formatCurrency($p['total_amount']) ?></td>
                        <td><?= formatCurrency($p['paid_amount']) ?></td>
                        <td>
                            <span class="badge <?= $p['payment_status'] === 'paid' ? 'bg-success' : ($p['payment_status'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                <?= ucfirst($p['payment_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/purchase/view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraScript = "<script>$(document).ready(function(){ initDataTable('#purchasesTable'); });</script>";
include __DIR__ . '/../includes/footer.php';
?>
