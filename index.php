<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Dashboard';
$breadcrumb = ['Dashboard' => ''];
$stats = getDashboardStats();
$lowStock = getLowStockProducts();

// Monthly chart data
$chartLabels = array_column($stats['monthlySales'], 'month');
$chartData   = array_column($stats['monthlySales'], 'total');

include __DIR__ . '/includes/header.php';
?>

<!-- Dashboard -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</h1>
        <p class="text-muted mb-0 small">Welcome back, <?= sanitize($_SESSION['user_name'] ?? 'Admin') ?>! Here's what's happening today.</p>
    </div>
    <a href="<?= APP_URL ?>/sales/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Invoice
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-blue h-100">
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
            <div class="stat-value"><?= number_format($stats['totalProducts']) ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-green h-100">
            <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-value"><?= formatCurrency($stats['todaySales']) ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-gold h-100">
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value"><?= formatCurrency($stats['totalProfit']) ?></div>
            <div class="stat-label">Total Profit</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-red h-100">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-value"><?= number_format($stats['lowStockCount']) ?></div>
            <div class="stat-label">Low Stock Alerts</div>
        </div>
    </div>
</div>

<!-- Charts & Tables Row -->
<div class="row g-3 mb-4">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart me-2"></i>Monthly Sales</span>
                <a href="<?= APP_URL ?>/reports/sales.php" class="btn btn-sm btn-outline-primary">View Report</a>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="90"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
            <div class="card-body d-flex flex-column gap-2 justify-content-center">
                <a href="<?= APP_URL ?>/sales/create.php" class="btn btn-primary w-100 text-start">
                    <i class="bi bi-receipt me-2"></i>Create New Invoice
                </a>
                <a href="<?= APP_URL ?>/products/add.php" class="btn btn-outline-primary w-100 text-start">
                    <i class="bi bi-plus-circle me-2"></i>Add Product
                </a>
                <a href="<?= APP_URL ?>/purchase/add.php" class="btn btn-outline-success w-100 text-start">
                    <i class="bi bi-cart-plus me-2"></i>Add Purchase
                </a>
                <a href="<?= APP_URL ?>/customers/index.php" class="btn btn-outline-info w-100 text-start">
                    <i class="bi bi-person-plus me-2"></i>Manage Customers
                </a>
                <a href="<?= APP_URL ?>/reports/sales.php" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-bar-chart me-2"></i>View Reports
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alerts -->
<?php if (!empty($lowStock)): ?>
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alerts</span>
                <a href="<?= APP_URL ?>/reports/stock.php" class="btn btn-sm btn-outline-danger">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>In Stock</th>
                                <th>Min. Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStock as $i => $product): ?>
                            <tr class="low-stock-row">
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= sanitize($product['product_name']) ?></strong></td>
                                <td><?= sanitize($product['category_name'] ?? '—') ?></td>
                                <td><?= sanitize($product['brand'] ?? '—') ?></td>
                                <td><span class="badge stock-badge-low"><?= $product['stock_quantity'] ?></span></td>
                                <td><?= $product['minimum_stock'] ?></td>
                                <td>
                                    <?php if ($product['stock_quantity'] == 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= APP_URL ?>/purchase/add.php?product_id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-cart-plus me-1"></i>Restock
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$extraScript = "
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: " . json_encode($chartLabels ?: ['No data']) . ",
        datasets: [{
            label: 'Sales (₹)',
            data: " . json_encode($chartData ?: [0]) . ",
            backgroundColor: 'rgba(30, 58, 95, 0.8)',
            borderRadius: 6,
            borderSkipped: false,
        }, {
            type: 'line',
            label: 'Trend',
            data: " . json_encode($chartData ?: [0]) . ",
            borderColor: '#f6ad1b',
            backgroundColor: 'rgba(246,173,27,0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#f6ad1b',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: ctx => '₹' + parseFloat(ctx.raw).toFixed(2).replace(/\d(?=(\d{3})+\.)/g,'$&,') } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '₹' + v.toLocaleString() } } }
    }
});
</script>
";
include __DIR__ . '/includes/footer.php';
?>
