<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Create User';
$breadcrumb = ['Users' => APP_URL . '/users/index.php', 'Create User' => ''];

$errors = [];
$formData = ['name' => '', 'email' => '', 'role' => 'staff'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name']  = sanitize($_POST['name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['role']  = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
    $password          = $_POST['password'] ?? '';
    $confirmPassword   = $_POST['confirm_password'] ?? '';

    if (empty($formData['name']))  $errors[] = 'Name is required.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email is required.';
    if (strlen($password) < 6)    $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'A user with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 1)")
               ->execute([$formData['name'], $formData['email'], $hash, $formData['role']]);
            setFlash('success', 'User "' . $formData['name'] . '" created successfully.');
            redirect(APP_URL . '/users/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/users/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="page-title"><i class="bi bi-person-plus me-2 text-primary"></i>Create New User</h1>
        <p class="text-muted mb-0 small">Fill in the details to create a new system user.</p>
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
            <div class="card-header"><i class="bi bi-person-badge me-2"></i>User Details</div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($formData['name']) ?>" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($formData['email']) ?>" placeholder="user@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select">
                            <option value="staff"  <?= $formData['role'] === 'staff'  ? 'selected' : '' ?>>Staff</option>
                            <option value="admin"  <?= $formData['role'] === 'admin'  ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <div class="form-text">Admins can manage users, Staff can access sales &amp; billing.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Min. 6 characters" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Repeat password" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Create User
                        </button>
                        <a href="<?= APP_URL ?>/users/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
