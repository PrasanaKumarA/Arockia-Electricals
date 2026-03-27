<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'User Management';
$breadcrumb = ['Users' => ''];
$users = getAllUsers();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-people-fill me-2 text-primary"></i>User Management</h1>
        <p class="text-muted mb-0 small">Manage system users and their roles.</p>
    </div>
    <a href="<?= APP_URL ?>/users/create.php" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Add New User
    </a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-people me-2"></i>All Users (<?= count($users) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="usersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle-sm"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                                <strong><?= sanitize($u['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-primary">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Staff</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['status']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['last_login'] ? formatDateTime($u['last_login']) : '<span class="text-muted">Never</span>' ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= APP_URL ?>/users/edit.php?id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" action="<?= APP_URL ?>/users/edit.php" class="d-inline"
                                      onsubmit="return confirm('Delete this user permanently?')">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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
$extraScript = '
<script>
$(document).ready(function() {
    $("#usersTable").DataTable({ pageLength: 25, order: [] });
});
</script>';
include __DIR__ . '/../includes/footer.php';
?>
