<?php
// Sidebar navigation
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function isActive(string $dir, string $file = ''): string {
    global $currentDir, $currentPage;
    if ($file) return ($currentDir === $dir && $currentPage === $file) ? 'active' : '';
    return $currentDir === $dir ? 'active' : '';
}
function isParentActive(array $dirs): string {
    global $currentDir;
    return in_array($currentDir, $dirs) ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= APP_URL ?>/assets/images/logo.png" alt="Logo" class="sidebar-logo">
        <div class="sidebar-brand-text">
            <span class="brand-name">Arockia</span>
            <span class="brand-sub">Electricals</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= (($currentDir === 'Arockia-Electricals' || $currentPage === 'index.php') && $currentDir !== 'products' && $currentDir !== 'sales' && $currentDir !== 'purchase' && $currentDir !== 'customers' && $currentDir !== 'suppliers' && $currentDir !== 'reports') ? 'active' : '' ?>" 
                   href="<?= APP_URL ?>/index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-section-title">Inventory</li>

            <!-- Products -->
            <li class="nav-item">
                <a class="nav-link <?= isParentActive(['products']) ?>" href="<?= APP_URL ?>/products/index.php">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                </a>
            </li>

            <!-- Suppliers -->
            <li class="nav-item">
                <a class="nav-link <?= isParentActive(['suppliers']) ?>" href="<?= APP_URL ?>/suppliers/index.php">
                    <i class="bi bi-truck"></i>
                    <span>Suppliers</span>
                </a>
            </li>

            <!-- Purchase -->
            <li class="nav-item">
                <a class="nav-link <?= isParentActive(['purchase']) ?>" href="<?= APP_URL ?>/purchase/index.php">
                    <i class="bi bi-cart-plus"></i>
                    <span>Purchases</span>
                </a>
            </li>

            <li class="nav-section-title">Sales</li>

            <!-- Customers -->
            <li class="nav-item">
                <a class="nav-link <?= isParentActive(['customers']) ?>" href="<?= APP_URL ?>/customers/index.php">
                    <i class="bi bi-people"></i>
                    <span>Customers</span>
                </a>
            </li>

            <!-- Sales / Invoices -->
            <li class="nav-item">
                <a class="nav-link <?= isParentActive(['sales']) ?>" href="<?= APP_URL ?>/sales/index.php">
                    <i class="bi bi-receipt"></i>
                    <span>Sales & Invoices</span>
                </a>
            </li>

            <li class="nav-section-title">Reports</li>

            <!-- Reports -->
            <li class="nav-item">
                <a class="nav-link <?= isActive('reports', 'sales.php') ?>" href="<?= APP_URL ?>/reports/sales.php">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Sales Report</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('reports', 'profit.php') ?>" href="<?= APP_URL ?>/reports/profit.php">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Profit Report</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('reports', 'stock.php') ?>" href="<?= APP_URL ?>/reports/stock.php">
                    <i class="bi bi-clipboard-data"></i>
                    <span>Stock Report</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar-circle-sm"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= sanitize($_SESSION['user_name'] ?? 'Admin') ?></span>
                <span class="sidebar-user-role"><?= ucfirst($_SESSION['user_role'] ?? 'Admin') ?></span>
            </div>
        </div>
        <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-danger logout-btn" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</aside>
