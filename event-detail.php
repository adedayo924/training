<?php
/**
 * Event Detail Page
 *
 * Shows event details and registration form.
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

// Get event by slug
$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM events WHERE slug = ? AND status = 'upcoming'");
$stmt->execute([$slug]);
$event = $stmt->fetch();

if (!$event) {
    // Try redirect to events listing
    header('Location: ' . APP_URL . '/events.php');
    exit;
}

// Check capacity
$regCount = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
$regCount->execute([$event['id']]);
$currentRegs = $regCount->fetchColumn();
$isFull = $event['capacity'] > 0 && $currentRegs >= $event['capacity'];

// Check for duplicate registration
$duplicateCheck = null;
if (!empty($_COOKIE['jcdw_reg_' . $event['id']])) {
    $duplicateCheck = 'You have already registered for this event.';
}

$pageTitle = $event['title'];
require_once __DIR__ . '/includes/public-header.php';
?>

<!-- Event Header -->
<section class="bg-success text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge bg-white text-success fs-6">
                        <?= ucfirst($event['type']) ?>
                    </span>
                    <span class="badge bg-light text-dark fs-6">
                        <?= date('M d, Y', strtotime($event['date'])) ?>
                    </span>
                </div>
                <h1 class="display-5 fw-bold mb-2"><?= e($event['title']) ?></h1>
                <p class="mb-0 opacity-75">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('l, F d, Y', strtotime($event['date'])) ?>
                    at <?= date('h:i A', strtotime($event['time'])) ?>
                    <?php if ($event['end_date']): ?>
                        — <?= date('l, F d, Y', strtotime($event['end_date'])) ?>
                        at <?= date('h:i A', strtotime($event['end_time'])) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Event Info -->
            <div class="col-lg-7">
                <?php if ($event['image']): ?>
                    <img src="uploads/events/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>"
                         class="img-fluid rounded shadow mb-4 w-100" style="max-height: 400px; object-fit: cover;">
                <?php endif; ?>

                <h3 class="fw-bold mb-3">About This Event</h3>
                <div class="event-description mb-4">
                    <?= nl2br(e($event['description'])) ?>
                </div>

                <!-- Event Details Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-success" aria-hidden="true"></i>Event Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Date</p>
                                <p class="fw-semibold mb-0">
                                    <i class="bi bi-calendar3 me-2 text-success" aria-hidden="true"></i>
                                    <?= date('l, M d, Y', strtotime($event['date'])) ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Time</p>
                                <p class="fw-semibold mb-0">
                                    <i class="bi bi-clock me-2 text-success" aria-hidden="true"></i>
                                    <?= date('h:i A', strtotime($event['time'])) ?>
                                    <?php if ($event['end_time']): ?>
                                        — <?= date('h:i A', strtotime($event['end_time'])) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Type</p>
                                <p class="fw-semibold mb-0">
                                    <span class="badge bg-<?= $event['type'] === 'online' ? 'primary' : 'success' ?> fs-6">
                                        <i class="bi bi-<?= $event['type'] === 'online' ? 'globe' : 'geo-alt' ?> me-1" aria-hidden="true"></i>
                                        <?= ucfirst($event['type']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Price</p>
                                <p class="fw-bold text-success mb-0 fs-4">
                                    <?= $event['price'] > 0 ? formatCurrency((float)$event['price']) : 'Free' ?>
                                </p>
                            </div>
                            <?php if ($event['venue']): ?>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Venue</p>
                                <p class="fw-semibold mb-0">
                                    <i class="bi bi-geo-alt me-2 text-success" aria-hidden="true"></i>
                                    <?= e($event['venue']) ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            <?php if ($event['meeting_link']): ?>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Meeting Link</p>
                                <p class="fw-semibold mb-0">
                                    <a href="<?= e($event['meeting_link']) ?>" target="_blank" class="text-success">
                                        <i class="bi bi-link-45deg me-1" aria-hidden="true"></i> Join Online
                                    </a>
                                </p>
                            </div>
                            <?php endif; ?>
                            <?php if ($event['capacity'] > 0): ?>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Capacity</p>
                                <p class="fw-semibold mb-0">
                                    <i class="bi bi-people me-2 text-success" aria-hidden="true"></i>
                                    <?= $currentRegs ?> / <?= $event['capacity'] ?> spots filled
                                </p>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= min(100, ($currentRegs / $event['capacity']) * 100) ?>%"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header bg-success text-white text-center py-3">
                        <h5 class="mb-0"><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Register Now</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($isFull): ?>
                            <div class="text-center py-3">
                                <i class="bi bi-calendar-x display-4 text-danger" aria-hidden="true"></i>
                                <p class="mt-2 fw-semibold text-danger">Event Full</p>
                                <p class="text-muted small">This event has reached its maximum capacity.</p>
                            </div>
                        <?php elseif ($duplicateCheck): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= $duplicateCheck ?>
                            </div>
                        <?php else: ?>
                            <div id="registrationForm">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Event Fee</span>
                                        <span class="fw-bold text-success fs-5">
                                            <?= $event['price'] > 0 ? formatCurrency((float)$event['price']) : 'Free' ?>
                                        </span>
                                    </div>
                                    <?php if ($event['capacity'] > 0): ?>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted small">Spots Left</span>
                                            <span class="small <?= $event['capacity'] - $currentRegs <= 5 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                <?= $event['capacity'] - $currentRegs ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <hr>

                                <form id="registerForm">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name"
                                               placeholder="Enter your full name" required minlength="2">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               placeholder="your@email.com" required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               placeholder="+234 xxx xxx xxxx" required>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 btn-lg" id="submitBtn">
                                        <?php if ($event['price'] > 0): ?>
                                            Pay <?= formatCurrency((float)$event['price']) ?> & Register
                                        <?php else: ?>
                                            Register for Free
                                        <?php endif; ?>
                                    </button>
                                </form>

                                <p class="text-muted text-center small mt-3 mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Your information is secure and will only be used for this event.
                                </p>
                            </div>

                            <div id="registrationSuccess" class="text-center py-4" style="display: none;">
                                <i class="bi bi-check-circle-fill text-success display-4"></i>
                                <h5 class="mt-3 fw-bold">Registration Submitted!</h5>
                                <p class="text-muted" id="successMessage">Check your email for confirmation.</p>
                            </div>

                            <div id="registrationError" class="alert alert-danger" style="display: none;">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <span id="errorMessage"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
const PAYSTACK_KEY = '<?= e(PAYSTACK_PUBLIC_KEY) ?>';
const EVENT_PRICE = <?= (float)$event['price'] ?>;
const EVENT_NAME = <?= json_encode($event['title']) ?>;
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
