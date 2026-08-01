<?php
/**
 * Admin User Delete
 *
 * POST-only. Prevents self-deletion. Only accessible to super_admins.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    setFlash('error', 'You do not have permission to perform this action.');
    redirect(APP_URL . '/admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(APP_URL . '/admin/admins.php');
}

requireCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid admin ID.');
    redirect(APP_URL . '/admin/admins.php');
}

// Prevent self-deletion
if ($id === $_SESSION['admin_id']) {
    setFlash('error', 'You cannot delete your own account.');
    redirect(APP_URL . '/admin/admins.php');
}

// Ensure at least one super_admin remains
$pdo = getDB();
$check = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
$check->execute([$id]);
$admin = $check->fetch();

if (!$admin) {
    setFlash('error', 'Admin not found.');
    redirect(APP_URL . '/admin/admins.php');
}

if ($admin['role'] === 'super_admin') {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'");
    $superAdminCount = $countStmt->fetchColumn();

    if ($superAdminCount <= 1) {
        setFlash('error', 'Cannot delete the last super admin.');
        redirect(APP_URL . '/admin/admins.php');
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Admin deleted successfully.');
    logAdminAction('admin_delete', "Deleted admin ID: {$id}");
} catch (PDOException $e) {
    setFlash('error', 'Failed to delete admin.');
    logError("Admin delete error for ID: {$id}", $e);
}

redirect(APP_URL . '/admin/admins.php');
