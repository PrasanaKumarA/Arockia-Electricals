<?php
// Shared header
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME;
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Arockia Electricals - Inventory Management & Billing System">
    <title><?= $pageTitle ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/custom.css">
    <!-- PWA Manifest (served via PHP to prevent InfinityFree injection) -->
    <link rel="manifest" href="<?= APP_URL ?>/manifest.php">
    <meta name="theme-color" content="#1e3a5f">
    <link rel="icon" href="<?= APP_URL ?>/assets/icons/icon-192.png">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
<div class="wrapper d-flex">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <!-- Top Navbar -->
        <nav class="top-navbar navbar navbar-expand-lg px-4">
            <button class="btn btn-link sidebar-toggle me-3 p-0" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <nav aria-label="breadcrumb" class="flex-grow-1">
                <ol class="breadcrumb mb-0">
                    <?php if (isset($breadcrumb)) foreach ($breadcrumb as $label => $url): ?>
                        <?php if ($url): ?>
                            <li class="breadcrumb-item"><a href="<?= $url ?>"><?= $label ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= $label ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <div class="navbar-nav ms-auto flex-row align-items-center gap-3">
                <!-- Dark mode toggle -->
                <button class="btn btn-link p-0 theme-toggle" id="themeToggle" title="Toggle dark mode">
                    <i class="bi bi-moon-stars fs-5"></i>
                </button>
                <!-- Notifications -->
                <?php $lowStock = getLowStockProducts(); ?>
                <div class="dropdown">
                    <button class="btn btn-link p-0 position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if (count($lowStock) > 0): ?>
                            <span class="badge bg-danger badge-notify"><?= count($lowStock) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow notify-dropdown" style="min-width:300px">
                        <h6 class="dropdown-header">Low Stock Alerts</h6>
                        <?php if (empty($lowStock)): ?>
                            <span class="dropdown-item text-muted">No alerts</span>
                        <?php else: ?>
                            <?php foreach (array_slice($lowStock, 0, 5) as $p): ?>
                                <a class="dropdown-item py-2" href="<?= APP_URL ?>/products/edit.php?id=<?= $p['id'] ?>">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-truncate me-2"><?= sanitize($p['product_name']) ?></span>
                                        <span class="badge bg-warning text-dark"><?= $p['stock_quantity'] ?> left</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center text-primary small" href="<?= APP_URL ?>/reports/stock.php">View all</a>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- User menu -->
                <div class="dropdown">
                    <button class="btn btn-link p-0 d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                        <div class="avatar-circle">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline fw-medium"><?= sanitize($_SESSION['user_name'] ?? 'Admin') ?></span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><h6 class="dropdown-header"><?= sanitize($_SESSION['user_name'] ?? '') ?></h6></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/users/edit.php?id=<?= $_SESSION['user_id'] ?? 0 ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
        <div class="px-4 pt-3">
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'x-circle' : 'info-circle') ?> me-2"></i>
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Content starts below -->
        <div class="content-area p-4">
