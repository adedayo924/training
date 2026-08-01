<?php
/**
 * Admin Header
 *
 * Contains the HTML head, navbar, and sidebar for the admin panel.
 * Requires $pageTitle to be set before including this file.
 */

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 250px; background: #1a1a2e; color: #fff;
            position: fixed; top: 0; left: 0; height: 100vh;
            overflow-y: auto; transition: transform 0.3s ease; z-index: 1040;
        }
        .admin-sidebar .sidebar-brand {
            padding: 20px; background: #146c43; text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }
        .admin-sidebar .sidebar-brand h5 { margin: 0; font-weight: 700; letter-spacing: 1px; }
        .admin-sidebar .sidebar-nav { padding: 15px 0; }
        .admin-sidebar .sidebar-nav a {
            display: flex; align-items: center; padding: 12px 20px;
            color: rgba(255,255,255,0.7); text-decoration: none;
            transition: all 0.2s; border-left: 3px solid transparent;
        }
        .admin-sidebar .sidebar-nav a:hover,
        .admin-sidebar .sidebar-nav a.active {
            background: rgba(255,255,255,0.1); color: #fff;
            border-left-color: #20c997;
        }
        .admin-sidebar .sidebar-nav a i { width: 24px; margin-right: 10px; text-align: center; }
        .admin-sidebar .sidebar-section { padding: 10px 20px 5px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.4); }
        .admin-content { flex: 1; margin-left: 250px; background: #f4f6f9; }
        .admin-topbar {
            background: #fff; padding: 15px 25px; border-bottom: 1px solid #e9ecef;
            display: flex; justify-content: space-between; align-items: center;
        }
        .admin-body { padding: 25px; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1035; }
        @media (max-width: 991.98px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.show { transform: translateX(0); }
            .admin-content { margin-left: 0; }
            .sidebar-overlay.show { display: block; }
        }
    </style>
</head>
<body>
<a href="#admin-content" class="visually-hidden-focusable">Skip to main content</a>
<div class="admin-wrapper">
    <!-- Sidebar -->
    <div class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <h5><i class="bi bi-calendar-event me-2"></i><?= e(APP_NAME) ?></h5>
            <small class="text-white-50">Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Main</div>
            <a href="index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <div class="sidebar-section">Management</div>
            <a href="events.php" class="<?= $currentPage === 'events' || $currentPage === 'event-form' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> Events
            </a>
            <a href="registrations.php" class="<?= $currentPage === 'registrations' || $currentPage === 'registration-detail' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Registrations
            </a>
            <?php if (($_SESSION['admin_role'] ?? '') === 'super_admin'): ?>
            <div class="sidebar-section">Administration</div>
            <a href="admins.php" class="<?= $currentPage === 'admins' || $currentPage === 'admin-form' ? 'active' : '' ?>">
                <i class="bi bi-shield-lock"></i> Admin Users
            </a>
            <?php endif; ?>
            <a href="settings.php" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">
                <i class="bi bi-gear"></i> Settings
            </a>
            <div class="sidebar-section mt-4">Quick Links</div>
            <a href="../index.php" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> View Website
            </a>
            <a href="logout.php">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="admin-content" id="admin-content">
        <div class="admin-topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary d-lg-none me-3" onclick="toggleSidebar()">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h5 class="mb-0 fw-semibold"><?= e($pageTitle) ?></h5>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-muted me-3 d-none d-md-inline">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= e($_SESSION['admin_name'] ?? 'Admin') ?>
                </span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="admin-body">
            <?php
            $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
