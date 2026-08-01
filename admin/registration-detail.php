<?php
/**
 * Admin Registration Detail
 *
 * Shows full details of a single registration.
 */

$pageTitle = 'Registration Details';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('error', 'Invalid registration ID.');
    redirect(APP_URL . '/admin/registrations.php');
}

$stmt = $pdo->prepare("
    SELECT r.*, e.title as event_title, e.date as event_date, e.time as event_time,
           e.type as event_type, e.venue, e.meeting_link, e.price as event_price
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$reg = $stmt->fetch();

if (!$reg) {
    setFlash('error', 'Registration not found.');
    redirect(APP_URL . '/admin/registrations.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="registrations.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Registrations
            </a>
            <span class="badge bg-<?= paymentStatusClass($reg['payment_status']) ?> fs-6">
                <?= ucfirst($reg['payment_status']) ?>
            </span>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-person me-2 text-success"></i>Attendee Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Full Name</label>
                        <p class="fw-semibold mb-0"><?= e($reg['full_name']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Email Address</label>
                        <p class="fw-semibold mb-0">
                            <a href="mailto:<?= e($reg['email']) ?>"><?= e($reg['email']) ?></a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Phone Number</label>
                        <p class="fw-semibold mb-0">
                            <a href="tel:<?= e($reg['phone']) ?>"><?= e($reg['phone']) ?></a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Registered</label>
                        <p class="fw-semibold mb-0"><?= date('M d, Y \a\t h:i A', strtotime($reg['registered_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2 text-success"></i>Event Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Event</label>
                        <p class="fw-semibold mb-0"><?= e($reg['event_title']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Type</label>
                        <p class="mb-0">
                            <?= eventTypeBadge($reg['event_type']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Date & Time</label>
                        <p class="fw-semibold mb-0">
                            <?= formatDate($reg['event_date']) ?> at <?= formatTime($reg['event_time']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Event Price</label>
                        <p class="fw-semibold mb-0">
                            <?= $reg['event_price'] > 0 ? formatCurrency((float)$reg['event_price']) : '<span class="text-success">Free</span>' ?>
                        </p>
                    </div>
                    <?php if ($reg['venue']): ?>
                    <div class="col-md-6">
                        <label class="text-muted small">Venue</label>
                        <p class="fw-semibold mb-0"><?= e($reg['venue']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($reg['meeting_link']): ?>
                    <div class="col-md-6">
                        <label class="text-muted small">Meeting Link</label>
                        <p class="mb-0">
                            <a href="<?= e($reg['meeting_link']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 300px;">
                                <?= e($reg['meeting_link']) ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-credit-card me-2 text-success"></i>Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="text-muted small">Payment Status</label>
                        <p class="mb-0">
                            <?= paymentStatusBadge($reg['payment_status']) ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Amount Paid</label>
                        <p class="fw-semibold mb-0"><?= formatCurrency((float)$reg['amount_paid']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Paystack Reference</label>
                        <p class="mb-0">
                            <?php if ($reg['paystack_reference']): ?>
                                <code><?= e($reg['paystack_reference']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">WhatsApp Notified</label>
                        <p class="mb-0">
                            <?php if ($reg['whatsapp_sent']): ?>
                                <span class="badge bg-success"><i class="bi bi-check"></i> Sent</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-x"></i> Not Sent</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
