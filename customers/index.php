<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$pageTitle = 'Customers';
$breadcrumb = ['Dashboard' => APP_URL . '/index.php', 'Customers' => ''];

$stmt = $db->query("SELECT * FROM customers WHERE status = 1 ORDER BY name");
$customers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-people me-2 text-primary"></i>Customers</h1>
        <p class="text-muted mb-0 small">Manage your customer records</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="openAddModal()">
        <i class="bi bi-person-plus me-1"></i>Add Customer
    </button>
</div>

<div class="card">
    <div class="card-header">Customers <span class="badge bg-primary ms-1"><?= count($customers) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="customersTable">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>GSTIN</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $i => $c): ?>
                    <tr id="row-customer-<?= $c['id'] ?>">
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($c['name']) ?></strong></td>
                        <td><?= sanitize($c['phone'] ?? '—') ?></td>
                        <td><?= sanitize($c['email'] ?? '—') ?></td>
                        <td><?= sanitize($c['address'] ?? '—') ?></td>
                        <td><?= sanitize($c['gstin'] ?? '—') ?></td>
                        <td>
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($c)) ?>)" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                            <button onclick="ajaxDelete('<?= APP_URL ?>/customers/ajax.php', <?= $c['id'] ?>, '#row-customer-<?= $c['id'] ?>')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="customerId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="cName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="cPhone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="cEmail" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin" id="cGstin" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="cAddress" class="form-control" rows="2"></textarea>
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
$(document).ready(function(){ initDataTable('#customersTable', {order:[[1,'asc']]}); });

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Customer';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
}
function openEditModal(c) {
    document.getElementById('modalTitle').textContent = 'Edit Customer';
    document.getElementById('customerId').value = c.id;
    document.getElementById('cName').value = c.name || '';
    document.getElementById('cPhone').value = c.phone || '';
    document.getElementById('cEmail').value = c.email || '';
    document.getElementById('cGstin').value = c.gstin || '';
    document.getElementById('cAddress').value = c.address || '';
    new bootstrap.Modal(document.getElementById('customerModal')).show();
}
document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = new FormData(this);
    data.append('action', document.getElementById('customerId').value ? 'update' : 'create');
    fetch('" . APP_URL . "/customers/ajax.php', { method:'POST', body: data })
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
