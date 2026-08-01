<?php
/**
 * Admin Users List
 *
 * Only accessible to super_admins.
 */

$pageTitle = 'Admin Users';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    setFlash('error', 'You do not have permission to access this page.');
    redirect(APP_URL . '/admin/index.php');
}

$pdo  = getDB();
$admins = $pdo->query("SELECT id, name, email, role, created_at FROM admins ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Total: <?= count($admins) ?> admin(s)</p>
    </div>
    <a href="admin-form.php" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i> Add Admin
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $adm): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?php if ($adm['id'] === $_SESSION['admin_id']): ?>
                                <i class="bi bi-person-circle me-1 text-success"></i>
                            <?php endif; ?>
                            <?= e($adm['name']) ?>
                        </td>
                        <td><?= e($adm['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $adm['role'] === 'super_admin' ? 'success' : 'secondary' ?>">
                                <?= $adm['role'] === 'super_admin' ? 'Super Admin' : 'Admin' ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= date('M d, Y', strtotime($adm['created_at'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="admin-form.php?edit=<?= $adm['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($adm['id'] !== $_SESSION['admin_id']): ?>
                                <form method="POST" action="admin-delete.php" class="d-inline btn-delete-admin-form" data-name="<?= e($adm['name']) ?>">
                                    <input type="hidden" name="id" value="<?= $adm['id'] ?>">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-delete-admin" title="Delete">
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

<script>
document.querySelectorAll('.btn-delete-admin').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var form = this.closest('.btn-delete-admin-form');
        var name = form ? form.dataset.name : 'this admin';
        if (!confirm('Are you sure you want to delete admin "' + name + '"?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
