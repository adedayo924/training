<?php
/**
 * Admin Settings
 *
 * Manage site settings stored in the database.
 */

$pageTitle = 'Settings';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $settings = [
        'site_name'           => trim($_POST['site_name'] ?? ''),
        'site_email'          => trim($_POST['site_email'] ?? ''),
        'site_phone'          => trim($_POST['site_phone'] ?? ''),
        'paystack_public_key' => trim($_POST['paystack_public_key'] ?? ''),
        'paystack_secret_key' => trim($_POST['paystack_secret_key'] ?? ''),
        'currency'            => trim($_POST['currency'] ?? 'NGN'),
        'currency_symbol'     => trim($_POST['currency_symbol'] ?? ''),
    ];

    foreach ($settings as $key => $value) {
        updateSetting($key, $value);
    }

    logAdminAction('settings_update', 'Updated site settings');
    $message = 'Settings updated successfully!';
}

// Load current settings
$siteName           = getSetting('site_name', APP_NAME);
$siteEmail          = getSetting('site_email');
$sitePhone          = getSetting('site_phone');
$paystackPublicKey  = getSetting('paystack_public_key', PAYSTACK_PUBLIC_KEY);
$paystackSecretKey  = getSetting('paystack_secret_key', PAYSTACK_SECRET_KEY);
$currency           = getSetting('currency', CURRENCY);
$currencySymbol     = getSetting('currency_symbol', CURRENCY_SYMBOL);

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <!-- Site Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-globe me-2 text-success"></i>Site Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Site Name</label>
                            <input type="text" class="form-control" name="site_name" value="<?= e($siteName) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" name="site_email" value="<?= e($siteEmail) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" name="site_phone" value="<?= e($sitePhone) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paystack Settings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-credit-card me-2 text-success"></i>Paystack Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Public Key</label>
                            <input type="text" class="form-control font-monospace" name="paystack_public_key" value="<?= e($paystackPublicKey) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Secret Key</label>
                            <input type="password" class="form-control font-monospace" name="paystack_secret_key" value="<?= e($paystackSecretKey) ?>">
                            <small class="text-muted">Enter the key or leave unchanged to keep the current one.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Currency -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-cash me-2 text-success"></i>Currency</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Currency Code</label>
                            <input type="text" class="form-control" name="currency" value="<?= e($currency) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" class="form-control" name="currency_symbol" value="<?= e($currencySymbol) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check-lg me-1"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
