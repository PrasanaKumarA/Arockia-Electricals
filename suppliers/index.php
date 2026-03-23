<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Suppliers';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Suppliers' => ''];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/ajax.php';
    exit();
}

$stmt = $db->query("SELECT * FROM suppliers WHERE status = 1 ORDER BY name");
$suppliers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-truck me-2 text-primary"></i>Suppliers</h1>
        <p class="text-muted mb-0 small">Manage your product suppliers</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="openAddModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Supplier
    </button>
</div>

<div class="card">
    <div class="card-header">Suppliers <span class="badge bg-primary ms-1"><?= count($suppliers) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="suppliersTable">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Company</th><th>Phone</th><th>Email</th><th>GSTIN</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $i => $s): ?>
                    <tr id="row-supplier-<?= $s['id'] ?>">
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($s['name']) ?></strong></td>
                        <td><?= sanitize($s['company'] ?? '—') ?></td>
                        <td><?= sanitize($s['phone'] ?? '—') ?></td>
                        <td><?= sanitize($s['email'] ?? '—') ?></td>
                        <td><?= sanitize($s['gstin'] ?? '—') ?></td>
                        <td>
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                            <button onclick="ajaxDelete('<?= APP_URL ?>/suppliers/ajax.php', <?= $s['id'] ?>, '#row-supplier-<?= $s['id'] ?>')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="supplierForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="supplierId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="sName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" id="sCompany" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="sPhone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="sEmail" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin" id="sGstin" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="sAddress" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScript = "
<script>
$(document).ready(function(){ initDataTable('#suppliersTable', {order:[[1,'asc']]}); });

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
}

function openEditModal(s) {
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('supplierId').value = s.id;
    document.getElementById('sName').value = s.name || '';
    document.getElementById('sCompany').value = s.company || '';
    document.getElementById('sPhone').value = s.phone || '';
    document.getElementById('sEmail').value = s.email || '';
    document.getElementById('sGstin').value = s.gstin || '';
    document.getElementById('sAddress').value = s.address || '';
    new bootstrap.Modal(document.getElementById('supplierModal')).show();
}

document.getElementById('supplierForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = new FormData(this);
    data.append('action', document.getElementById('supplierId').value ? 'update' : 'create');
    fetch('" . APP_URL . "/suppliers/ajax.php', { method:'POST', body: data })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast(res.message); setTimeout(() => location.reload(), 1000); }
        else showToast(res.message, 'error');
    });
});
</script>
";
include __DIR__ . '/../includes/footer.php';
?>
