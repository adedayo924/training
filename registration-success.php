<?php
/**
 * Registration Success Page
 *
 * Shown after successful registration or payment.
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$reg = $_SESSION['last_registration'] ?? null;

if (!$reg) {
    redirect(APP_URL . '/events.php');
}

// Generate WhatsApp message
$whatsappMessage = "Hello! I just registered for {$reg['event_name']} on " . date('M d, Y', strtotime($reg['event_date'])) . ". My registration was successful. Reference: {$reg['reference']}";
$whatsappLink = 'https://wa.me/?text=' . urlencode($whatsappMessage);

$pageTitle = 'Registration Successful';
require_once __DIR__ . '/includes/public-header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <div class="success-checkmark mx-auto">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                            </div>
                        </div>

                        <h2 class="fw-bold text-success mb-2">Registration Successful!</h2>
                        <p class="text-muted mb-4">Thank you for registering. You're all set!</p>

                        <div class="bg-light rounded-3 p-4 mb-4 text-start">
                            <h6 class="fw-bold mb-3"><i class="bi bi-ticket-perforated me-2 text-success"></i>Registration Details</h6>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Name</small>
                                    <span class="fw-semibold"><?= e($reg['name']) ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Email</small>
                                    <span class="fw-semibold"><?= e($reg['email']) ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Event</small>
                                    <span class="fw-semibold"><?= e($reg['event_name']) ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Date</small>
                                    <span class="fw-semibold">
                                        <?= date('M d, Y', strtotime($reg['event_date'])) ?>
                                        at <?= date('h:i A', strtotime($reg['event_time'])) ?>
                                    </span>
                                </div>
                                <?php if ($reg['amount'] > 0): ?>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Amount Paid</small>
                                    <span class="fw-bold text-success"><?= formatCurrency((float)$reg['amount']) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Reference</small>
                                    <span class="font-monospace small"><?= e($reg['reference']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info text-start">
                            <h6 class="fw-bold mb-2"><i class="bi bi-envelope me-2"></i>Confirmation Email</h6>
                            <p class="mb-0 small">
                                A confirmation email has been sent to <strong><?= e($reg['email']) ?></strong>.
                                Please check your inbox (and spam folder).
                            </p>
                        </div>

                        <?php if ($reg['event_type'] === 'online' || $reg['event_type'] === 'hybrid'): ?>
                            <div class="alert alert-success text-start">
                                <h6 class="fw-bold mb-2"><i class="bi bi-camera-video me-2"></i>Online Event</h6>
                                <p class="mb-0 small">
                                    A meeting link will be sent to your email before the event starts.
                                    Make sure to check your inbox regularly.
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mt-4">
                            <a href="<?= e($whatsappLink) ?>" target="_blank" class="btn btn-success btn-lg">
                                <i class="bi bi-whatsapp me-2"></i>Share on WhatsApp
                            </a>
                            <a href="event-detail.php?slug=<?= e($reg['event_slug'] ?? '') ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-eye me-2"></i>View Event
                            </a>
                        </div>

                        <hr class="my-4">

                        <a href="<?= e(APP_URL) ?>" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Back to Homepage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Clear the session data
unset($_SESSION['last_registration']);

require_once __DIR__ . '/includes/public-footer.php';
?>
