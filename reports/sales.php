<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Sales Report';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Reports' => '#', 'Sales Report' => ''];

// Filters
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo   = sanitize($_GET['date_to']   ?? date('Y-m-d'));
$category = (int)($_GET['category'] ?? 0);
$period   = sanitize($_GET['period'] ?? 'custom');

// Quick period presets
if ($period === 'today') { $dateFrom = $dateTo = date('Y-m-d'); }
elseif ($period === 'week') { $dateFrom = date('Y-m-d', strtotime('monday this week')); $dateTo = date('Y-m-d'); }
elseif ($period === 'month') { $dateFrom = date('Y-m-01'); $dateTo = date('Y-m-d'); }

// Query
$where = ["s.status = 1", "s.sale_date BETWEEN ? AND ?"];
$params = [$dateFrom, $dateTo];

$salesStmt = $db->prepare("SELECT s.*, c.name as customer_name 
                           FROM sales s LEFT JOIN customers c ON s.customer_id = c.id 
                           WHERE " . implode(' AND ', $where) . " ORDER BY s.sale_date DESC, s.id DESC");
$salesStmt->execute($params);
$sales = $salesStmt->fetchAll();

// Summary stats
$totalRevenue = array_sum(array_column($sales, 'total_amount'));
$totalCount   = count($sales);

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Invoice No', 'Customer', 'Date', 'Subtotal', 'Discount', 'GST', 'Total', 'Payment', 'Status']);
    foreach ($sales as $s) {
        fputcsv($out, [$s['invoice_number'], $s['customer_name'] ?? 'Walk-in', $s['sale_date'], $s['subtotal'], $s['discount'], $s['gst_amount'], $s['total_amount'], $s['payment_method'], $s['payment_status']]);
    }
    fclose($out);
    exit();
}

$categories = getCategories();
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Sales Report</h1>
        <p class="text-muted mb-0 small">Analyze your sales performance</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>&export=csv" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Export CSV</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <div class="btn-group">
                    <a href="?period=today" class="btn btn-sm <?= $period==='today'?'btn-primary':'btn-outline-secondary' ?>">Today</a>
                    <a href="?period=week" class="btn btn-sm <?= $period==='week'?'btn-primary':'btn-outline-secondary' ?>">This Week</a>
                    <a href="?period=month" class="btn btn-sm <?= $period==='month'?'btn-primary':'btn-outline-secondary' ?>">This Month</a>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
            </div>
            <div class="col-auto">
                <button type="submit" name="period" value="custom" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card stat-blue"><div class="stat-icon"><i class="bi bi-receipt"></i></div>
        <div class="stat-value"><?= $totalCount ?></div><div class="stat-label">Total Invoices</div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-green"><div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
        <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div><div class="stat-label">Total Revenue</div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-gold"><div class="stat-icon"><i class="bi bi-calculator"></i></div>
        <div class="stat-value"><?= $totalCount > 0 ? formatCurrency($totalRevenue / $totalCount) : '₹0.00' ?></div>
        <div class="stat-label">Avg. Invoice Value</div></div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">Sales — <?= formatDate($dateFrom) ?> to <?= formatDate($dateTo) ?> <span class="badge bg-primary ms-1"><?= $totalCount ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="salesReportTable">
                <thead><tr><th>#</th><th>Invoice</th><th>Customer</th><th>Date</th><th>Subtotal</th><th>Discount</th><th>GST</th><th>Total</th><th>Method</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($sales as $i => $s): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><a href="<?= APP_URL ?>/sales/view.php?id=<?= $s['id'] ?>"><?= sanitize($s['invoice_number']) ?></a></td>
                        <td><?= sanitize($s['customer_name'] ?? 'Walk-in') ?></td>
                        <td><?= formatDate($s['sale_date']) ?></td>
                        <td><?= formatCurrency($s['subtotal']) ?></td>
                        <td><?= $s['discount'] > 0 ? formatCurrency($s['discount']) : '—' ?></td>
                        <td><?= $s['gst_amount'] > 0 ? formatCurrency($s['gst_amount']) : '—' ?></td>
                        <td class="fw-bold"><?= formatCurrency($s['total_amount']) ?></td>
                        <td><?= ucfirst($s['payment_method'] ?? 'cash') ?></td>
                        <td><span class="badge <?= $s['payment_status']==='paid'?'bg-success':($s['payment_status']==='partial'?'bg-warning text-dark':'bg-danger') ?>"><?= ucfirst($s['payment_status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="7" class="text-end">Grand Total:</td>
                        <td><?= formatCurrency($totalRevenue) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
$extraScript = "<script>$(document).ready(function(){ initDataTable('#salesReportTable', {order:[[3,'desc']], pageLength:25}); });</script>";
include __DIR__ . '/../includes/footer.php';
?>
