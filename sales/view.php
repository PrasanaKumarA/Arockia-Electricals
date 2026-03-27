<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/sales/index.php');

$stmt = $db->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, c.gstin as customer_gstin
                      FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ? AND s.status = 1");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) redirect(APP_URL . '/sales/index.php');

$items = $db->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

$whatsAppLink = getWhatsAppLink($sale['invoice_number'], $sale['customer_name'] ?? 'Customer', $sale['total_amount'], $sale['customer_phone'] ?? '');

$pageTitle = 'Invoice ' . $sale['invoice_number'];
$breadcrumb = ['Sales' => APP_URL . '/sales/index.php', $sale['invoice_number'] => ''];
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h1 class="page-title"><i class="bi bi-receipt me-2 text-primary"></i><?= sanitize($sale['invoice_number']) ?></h1>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/sales/edit.php?id=<?= $sale['id'] ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/sales/generate_pdf.php?id=<?= $sale['id'] ?>" class="btn btn-danger" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="<?= $whatsAppLink ?>" class="btn btn-success" target="_blank"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
        <a href="<?= APP_URL ?>/sales/send_email.php?id=<?= $sale['id'] ?>" class="btn btn-info"><i class="bi bi-envelope me-1"></i>Email</a>
        <a href="<?= APP_URL ?>/sales/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

<!-- Invoice Card -->
<div class="card" id="invoice-print-area">
    <!-- Header -->
    <div class="invoice-header p-4">
        <div class="row align-items-center">
            <div class="col-md-6 d-flex align-items-center gap-3">
                <img src="<?= APP_URL ?>/assets/images/logo.png" alt="Logo" style="width:60px;height:60px;border-radius:10px;">
                <div>
                    <h2 class="mb-0 fw-bold fs-4"><?= COMPANY_NAME ?></h2>
                    <p class="mb-0 opacity-75 small"><?= COMPANY_ADDRESS ?></p>
                    <p class="mb-0 opacity-75 small"><?= COMPANY_PHONE ?> | <?= COMPANY_EMAIL ?></p>
                    <?php if (COMPANY_GSTIN): ?><p class="mb-0 opacity-75 small">GSTIN: <?= COMPANY_GSTIN ?></p><?php endif; ?>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <h3 class="mb-1 fw-bold">TAX INVOICE</h3>
                <p class="mb-0 opacity-90"><strong><?= sanitize($sale['invoice_number']) ?></strong></p>
                <p class="mb-0 opacity-75">Date: <?= formatDate($sale['sale_date']) ?></p>
                <span class="badge <?= $sale['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?> mt-1">
                    <?= strtoupper($sale['payment_status']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="p-4">
        <!-- Customer Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 rounded" style="background:var(--body-bg)">
                    <h6 class="text-muted text-uppercase mb-2" style="font-size:0.7rem;letter-spacing:.05em">Billed To</h6>
                    <p class="mb-1 fw-bold fs-6"><?= sanitize($sale['customer_name'] ?? 'Walk-in Customer') ?></p>
                    <?php if ($sale['customer_phone']): ?><p class="mb-1 small text-muted"><i class="bi bi-telephone me-1"></i><?= sanitize($sale['customer_phone']) ?></p><?php endif; ?>
                    <?php if ($sale['customer_email']): ?><p class="mb-1 small text-muted"><i class="bi bi-envelope me-1"></i><?= sanitize($sale['customer_email']) ?></p><?php endif; ?>
                    <?php if ($sale['customer_address']): ?><p class="mb-0 small text-muted"><i class="bi bi-geo-alt me-1"></i><?= sanitize($sale['customer_address']) ?></p><?php endif; ?>
                    <?php if ($sale['customer_gstin']): ?><p class="mb-0 small text-muted">GSTIN: <?= sanitize($sale['customer_gstin']) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded" style="background:var(--body-bg)">
                    <h6 class="text-muted text-uppercase mb-2" style="font-size:0.7rem;letter-spacing:.05em">Payment Info</h6>
                    <p class="mb-1"><strong>Method:</strong> <?= ucfirst($sale['payment_method'] ?? 'Cash') ?></p>
                    <p class="mb-1"><strong>Status:</strong> <?= ucfirst($sale['payment_status']) ?></p>
                    <p class="mb-0"><strong>Amount Paid:</strong> <?= formatCurrency($sale['paid_amount']) ?></p>
                    <?php $due = $sale['total_amount'] - $sale['paid_amount']; ?>
                    <?php if ($due > 0): ?><p class="mb-0 text-danger"><strong>Balance Due:</strong> <?= formatCurrency($due) ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table">
                <thead style="background:var(--primary);color:#fff">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($item['product_name']) ?></strong></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end"><?= formatCurrency($item['selling_price']) ?></td>
                        <td class="text-end"><?= formatCurrency($item['total_price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="row justify-content-end">
            <div class="col-md-5">
                <div class="invoice-total-box">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span><?= formatCurrency($sale['subtotal']) ?></span>
                    </div>
                    <?php if ($sale['discount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Discount</span>
                        <span>- <?= formatCurrency($sale['discount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($sale['gst_rate'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">GST (<?= $sale['gst_rate'] ?>%)</span>
                        <span><?= formatCurrency($sale['gst_amount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Grand Total</span>
                        <span class="text-primary"><?= formatCurrency($sale['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes & Footer -->
        <?php if ($sale['notes']): ?>
        <div class="mt-4 p-3 rounded" style="background:var(--body-bg)">
            <strong>Notes:</strong> <?= sanitize($sale['notes']) ?>
        </div>
        <?php endif; ?>

        <div class="mt-4 pt-3 border-top text-center text-muted small">
            <p class="mb-1">Thank you for your business! 🙏</p>
            <p class="mb-0"><?= COMPANY_NAME ?> | <?= COMPANY_PHONE ?> | <?= COMPANY_WEBSITE ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
