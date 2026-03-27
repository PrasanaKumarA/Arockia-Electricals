<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

// ── Handle DELETE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId && $deleteId !== (int)$_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$deleteId]);
        setFlash('success', 'User deleted successfully.');
    } else {
        setFlash('error', 'Cannot delete your own account.');
    }
    redirect(APP_URL . '/users/index.php');
}

// ── Load user ────────────────────────────────────────────────
$id   = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/users/index.php');
$user = getUserById($id);
if (!$user) {
    setFlash('error', 'User not found.');
    redirect(APP_URL . '/users/index.php');
}

$pageTitle = 'Edit User';
$breadcrumb = ['Users' => APP_URL . '/users/index.php', 'Edit User' => ''];

$errors = [];
$formData = [
    'name'   => $user['name'],
    'email'  => $user['email'],
    'role'   => $user['role'],
    'status' => $user['status'],
];

// ── Handle UPDATE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $formData['name']   = sanitize($_POST['name'] ?? '');
    $formData['email']  = sanitize($_POST['email'] ?? '');
    $formData['role']   = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
    $formData['status'] = isset($_POST['status']) ? 1 : 0;
    $newPassword        = $_POST['password'] ?? '';
    $confirmPassword    = $_POST['confirm_password'] ?? '';

    if (empty($formData['name']))  $errors[] = 'Name is required.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email is required.';
    if (!empty($newPassword) && strlen($newPassword) < 6)
        $errors[] = 'Password must be at least 6 characters.';
    if (!empty($newPassword) && $newPassword !== $confirmPassword)
        $errors[] = 'Passwords do not match.';

    // Check duplicate email (excluding current user)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$formData['email'], $id]);
    if ($stmt->fetch()) $errors[] = 'Another user with this email already exists.';

    if (empty($errors)) {
        if (!empty($newPassword)) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->prepare("UPDATE users SET name=?, email=?, role=?, status=?, password=? WHERE id=?")
               ->execute([$formData['name'], $formData['email'], $formData['role'], $formData['status'], $hash, $id]);
        } else {
            $db->prepare("UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?")
               ->execute([$formData['name'], $formData['email'], $formData['role'], $formData['status'], $id]);
        }
        // Update session if editing own account name
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['user_name'] = $formData['name'];
            $_SESSION['user_role'] = $formData['role'];
        }
        setFlash('success', 'User updated successfully.');
        redirect(APP_URL . '/users/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/users/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="page-title"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit User</h1>
        <p class="text-muted mb-0 small">Update details for <strong><?= sanitize($user['name']) ?></strong>.</p>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-gear me-2"></i>User Details</div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="update">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($formData['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($formData['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" <?= $id === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                            <option value="staff" <?= $formData['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <?php if ($id === (int)$_SESSION['user_id']): ?>
                            <input type="hidden" name="role" value="<?= $formData['role'] ?>">
                            <div class="form-text text-warning"><i class="bi bi-info-circle me-1"></i>Cannot change your own role.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="statusToggle"
                                   <?= $formData['status'] ? 'checked' : '' ?>
                                   <?= $id === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="statusToggle">Active</label>
                        </div>
                        <?php if ($id === (int)$_SESSION['user_id']): ?>
                            <input type="hidden" name="status" value="1">
                        <?php endif; ?>
                    </div>
                    <hr>
                    <p class="text-muted small"><i class="bi bi-lock me-1"></i>Leave password blank to keep current password.</p>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 characters">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                        <a href="<?= APP_URL ?>/users/index.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php if ($id !== (int)$_SESSION['user_id']): ?>
                        <button type="button" class="btn btn-outline-danger ms-auto"
                                onclick="document.getElementById('deleteForm').submit()">
                            <i class="bi bi-trash me-1"></i>Delete User
                        </button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($id !== (int)$_SESSION['user_id']): ?>
                <form id="deleteForm" method="POST" onsubmit="return confirm('Delete this user permanently?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $id ?>">
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
