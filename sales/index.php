<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Sales & Invoices';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Sales' => ''];

$stmt = $db->query("SELECT s.*, c.name as customer_name, c.phone as customer_phone 
                    FROM sales s LEFT JOIN customers c ON s.customer_id = c.id 
                    WHERE s.status = 1 ORDER BY s.id DESC");
$sales = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-receipt me-2 text-primary"></i>Sales & Invoices</h1>
        <p class="text-muted mb-0 small">All sales invoices</p>
    </div>
    <a href="<?= APP_URL ?>/sales/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Invoice
    </a>
</div>

<div class="card">
    <div class="card-header">Invoices <span class="badge bg-primary ms-1"><?= count($sales) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="salesTable">
                <thead>
                    <tr><th>#</th><th>Invoice No.</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $i => $s): ?>
                    <tr id="row-sale-<?= $s['id'] ?>">
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($s['invoice_number']) ?></strong></td>
                        <td><?= sanitize($s['customer_name'] ?? 'Walk-in') ?></td>
                        <td><?= formatDate($s['sale_date']) ?></td>
                        <td class="fw-semibold"><?= formatCurrency($s['total_amount']) ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst($s['payment_method'] ?? 'cash') ?></span></td>
                        <td>
                            <span class="badge <?= $s['payment_status'] === 'paid' ? 'bg-success' : ($s['payment_status'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                <?= ucfirst($s['payment_status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= APP_URL ?>/sales/view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="<?= APP_URL ?>/sales/generate_pdf.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" target="_blank" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                <button onclick="ajaxDelete('<?= APP_URL ?>/sales/delete.php', <?= $s['id'] ?>, '#row-sale-<?= $s['id'] ?>')" class="btn btn-sm btn-outline-secondary" title="Delete"><i class="bi bi-trash"></i></button>
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
$extraScript = "<script>$(document).ready(function(){ initDataTable('#salesTable'); });</script>";
include __DIR__ . '/../includes/footer.php';
?>
