<?php
/**
 * Admin User Form
 *
 * Add/Edit admin users. Only accessible to super_admins.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    setFlash('error', 'You do not have permission to access this page.');
    redirect(APP_URL . '/admin/index.php');
}

$pdo    = getDB();
$errors = [];
$admin  = null;
$isEdit = false;
$action = 'Add';
$editId = (int)($_GET['edit'] ?? 0);

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM admins WHERE id = ?");
    $stmt->execute([$editId]);
    $admin = $stmt->fetch();

    if (!$admin) {
        setFlash('error', 'Admin not found.');
        redirect(APP_URL . '/admin/admins.php');
    }

    $isEdit = true;
    $action = 'Edit';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $isSuperAdmin = ($_SESSION['admin_role'] ?? '') === 'super_admin';
    $role     = $isSuperAdmin ? ($_POST['role'] ?? 'admin') : 'admin';

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (!$isEdit && empty($password)) {
        $errors[] = 'Password is required for new admins.';
    }
    if (!empty($password) && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!empty($password) && $password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['super_admin', 'admin'])) {
        $role = 'admin';
    }

    // Check email uniqueness
    $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $editId]);
    if ($emailCheck->fetchColumn() > 0) {
        $errors[] = 'An admin with this email already exists.';
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $hashed, $role, $editId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $editId]);
                }

                setFlash('success', 'Admin updated successfully!');

                // Update session if editing self
                if ($editId === $_SESSION['admin_id']) {
                    $_SESSION['admin_name'] = $name;
                    $_SESSION['admin_email'] = $email;
                    if ($isSuperAdmin) {
                        $_SESSION['admin_role'] = $role;
                    }
                }
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed, $role]);

                setFlash('success', 'Admin created successfully!');
            }

            redirect(APP_URL . '/admin/admins.php');
        } catch (PDOException $e) {
            $errors[] = 'Database error. Please try again.';
            logError("Admin save error", $e);
        }
    }
}

$pageTitle = $action . ' Admin';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?= e($admin['name'] ?? ($_POST['name'] ?? '')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($admin['email'] ?? ($_POST['email'] ?? '')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password <?= $isEdit ? '(leave blank to keep current)' : '*' ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               <?= $isEdit ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">
                            Confirm Password <?= $isEdit ? '' : '*' ?>
                        </label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                               <?= $isEdit ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                    </div>

                    <?php if (($_SESSION['admin_role'] ?? '') === 'super_admin'): ?>
                    <div class="mb-4">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="admin" <?= ($admin['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="super_admin" <?= ($admin['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between">
                        <a href="admins.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Update' : 'Create' ?> Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
