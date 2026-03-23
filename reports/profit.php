<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Profit Report';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Reports' => '#', 'Profit Report' => ''];

$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo   = sanitize($_GET['date_to']   ?? date('Y-m-d'));
$period   = sanitize($_GET['period'] ?? 'custom');
if ($period === 'today') { $dateFrom = $dateTo = date('Y-m-d'); }
elseif ($period === 'week') { $dateFrom = date('Y-m-d', strtotime('monday this week')); $dateTo = date('Y-m-d'); }
elseif ($period === 'month') { $dateFrom = date('Y-m-01'); $dateTo = date('Y-m-d'); }

$stmt = $db->prepare("SELECT 
    si.product_name,
    SUM(si.quantity) as total_qty,
    AVG(si.purchase_price) as avg_cost,
    AVG(si.selling_price) as avg_price,
    SUM(si.total_price) as total_revenue,
    SUM(si.quantity * si.purchase_price) as total_cost,
    SUM((si.selling_price - si.purchase_price) * si.quantity) as profit
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    WHERE s.status = 1 AND s.sale_date BETWEEN ? AND ?
    GROUP BY si.product_name
    ORDER BY profit DESC");
$stmt->execute([$dateFrom, $dateTo]);
$rows = $stmt->fetchAll();

$totalRevenue = array_sum(array_column($rows, 'total_revenue'));
$totalCost    = array_sum(array_column($rows, 'total_cost'));
$totalProfit  = array_sum(array_column($rows, 'profit'));

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="profit-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Product', 'Qty Sold', 'Avg Cost', 'Avg Price', 'Revenue', 'Cost', 'Profit', 'Margin %']);
    foreach ($rows as $r) {
        $margin = $r['total_revenue'] > 0 ? round($r['profit'] / $r['total_revenue'] * 100, 1) : 0;
        fputcsv($out, [$r['product_name'], $r['total_qty'], $r['avg_cost'], $r['avg_price'], $r['total_revenue'], $r['total_cost'], $r['profit'], $margin . '%']);
    }
    fclose($out);
    exit();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Profit Report</h1>
        <p class="text-muted mb-0 small">Profit analysis by product</p>
    </div>
    <a href="?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>&export=csv" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Export CSV</a>
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
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>"></div>
            <div class="col-auto"><button type="submit" name="period" value="custom" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card stat-blue"><div class="stat-icon"><i class="bi bi-currency-rupee"></i></div><div class="stat-value"><?= formatCurrency($totalRevenue) ?></div><div class="stat-label">Total Revenue</div></div></div>
    <div class="col-md-4"><div class="stat-card stat-red"><div class="stat-icon"><i class="bi bi-cart-x"></i></div><div class="stat-value"><?= formatCurrency($totalCost) ?></div><div class="stat-label">Total Cost</div></div></div>
    <div class="col-md-4"><div class="stat-card stat-green"><div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div><div class="stat-value"><?= formatCurrency($totalProfit) ?></div><div class="stat-label">Net Profit</div></div></div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">Profit by Product — <?= formatDate($dateFrom) ?> to <?= formatDate($dateTo) ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="profitTable">
                <thead><tr><th>#</th><th>Product</th><th>Qty Sold</th><th>Cost/Unit</th><th>Price/Unit</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Margin %</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                    <?php $margin = $r['total_revenue'] > 0 ? round($r['profit'] / $r['total_revenue'] * 100, 1) : 0; ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><strong><?= sanitize($r['product_name']) ?></strong></td>
                        <td><?= $r['total_qty'] ?></td>
                        <td><?= formatCurrency($r['avg_cost']) ?></td>
                        <td><?= formatCurrency($r['avg_price']) ?></td>
                        <td><?= formatCurrency($r['total_revenue']) ?></td>
                        <td><?= formatCurrency($r['total_cost']) ?></td>
                        <td class="fw-bold <?= $r['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatCurrency($r['profit']) ?></td>
                        <td><span class="badge <?= $margin >= 20 ? 'bg-success' : ($margin >= 10 ? 'bg-warning text-dark' : 'bg-danger') ?>"><?= $margin ?>%</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="5" class="text-end">Totals:</td>
                        <td><?= formatCurrency($totalRevenue) ?></td>
                        <td><?= formatCurrency($totalCost) ?></td>
                        <td class="text-success"><?= formatCurrency($totalProfit) ?></td>
                        <td><?= $totalRevenue > 0 ? round($totalProfit / $totalRevenue * 100, 1) : 0 ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
$extraScript = "<script>$(document).ready(function(){ initDataTable('#profitTable', {order:[[7,'desc']], pageLength:25}); });</script>";
include __DIR__ . '/../includes/footer.php';
?>
