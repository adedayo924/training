<?php
/**
 * Homepage
 *
 * Public landing page with hero section and upcoming events.
 */

$pageTitle = 'Home';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

// Single query for registration counts per event (used by featured + upcoming)
$regCountsStmt = $pdo->query("SELECT event_id, COUNT(*) as cnt FROM registrations GROUP BY event_id");
$regCounts = [];
while ($row = $regCountsStmt->fetch()) {
    $regCounts[$row['event_id']] = $row['cnt'];
}

// Featured events
$featuredStmt = $pdo->query("
    SELECT e.*
    FROM events e
    WHERE e.is_featured = 1 AND e.status = 'upcoming' AND e.date >= CURDATE()
    ORDER BY e.date ASC
    LIMIT 3
");
$featuredEvents = $featuredStmt->fetchAll();
foreach ($featuredEvents as &$evt) {
    $evt['reg_count'] = $regCounts[$evt['id']] ?? 0;
}
unset($evt);

// Upcoming events
$upcomingStmt = $pdo->query("
    SELECT e.*
    FROM events e
    WHERE e.status = 'upcoming' AND e.date >= CURDATE()
    ORDER BY e.date ASC
    LIMIT 6
");
$upcomingEvents = $upcomingStmt->fetchAll();
foreach ($upcomingEvents as &$evt) {
    $evt['reg_count'] = $regCounts[$evt['id']] ?? 0;
}
unset($evt);

// Stats in a single query
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM events WHERE status = 'upcoming') as total_events,
        (SELECT COUNT(*) FROM registrations) as total_regs,
        (SELECT COUNT(DISTINCT email) FROM registrations WHERE payment_status IN ('free','paid')) as total_trained
")->fetch();

require_once __DIR__ . '/includes/public-header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-white text-center">
    <div class="hero-overlay"></div>
    <div class="container position-relative py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3 animate-fade-in">Welcome to <?= e(APP_NAME) ?></h1>
                <p class="lead mb-4 animate-fade-in">
                    Join our professional webinars, trainings, and events.
                    Build skills, connect with experts, and advance your career.
                </p>
                <div class="animate-fade-in">
                    <a href="events.php" class="btn btn-light btn-lg px-5 me-3">
                        <i class="bi bi-calendar3 me-2" aria-hidden="true"></i> View Events
                    </a>
                    <a href="#events" class="btn btn-outline-light btn-lg px-5">
                        <i class="bi bi-arrow-down me-2" aria-hidden="true"></i> Explore
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4">
                    <h2 class="fw-bold text-success"><?= $stats['total_events'] ?></h2>
                    <p class="text-muted mb-0">Upcoming Events</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <h2 class="fw-bold text-success"><?= $stats['total_regs'] ?></h2>
                    <p class="text-muted mb-0">Total Registrations</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <h2 class="fw-bold text-success"><?= $stats['total_trained'] ?></h2>
                    <p class="text-muted mb-0">People Trained</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($featuredEvents)): ?>
<!-- Featured Events -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Featured Events</h2>
            <p class="text-muted">Don't miss these highlighted opportunities</p>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredEvents as $evt): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm event-card">
                    <?php if ($evt['image']): ?>
                        <img src="uploads/events/<?= e($evt['image']) ?>" class="card-img-top" alt="<?= e($evt['title']) ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-calendar-event display-1 text-success" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex gap-2 mb-2">
                            <?= eventTypeBadge($evt['type']) ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1" aria-hidden="true"></i>Featured</span>
                        </div>
                        <h5 class="card-title fw-bold"><?= e($evt['title']) ?></h5>
                        <p class="card-text text-muted small">
                            <i class="bi bi-calendar3 me-1"></i> <?= formatDate($evt['date']) ?>
                            at <?= formatTime($evt['time']) ?>
                            <br>
                            <i class="bi bi-people me-1"></i> <?= $evt['reg_count'] ?> registered
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-success">
                                <?= $evt['price'] > 0 ? formatCurrency((float)$evt['price']) : 'Free' ?>
                            </span>
                            <a href="event-detail.php?slug=<?= e($evt['slug']) ?>" class="btn btn-success btn-sm">
                                View & Register <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- All Upcoming Events -->
<section class="py-5 bg-light" id="events">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Upcoming Events</h2>
            <p class="text-muted">Browse and register for our latest events</p>
        </div>

        <?php if (empty($upcomingEvents)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x display-1 text-muted"></i>
                <h4 class="mt-3 text-muted">No upcoming events at the moment</h4>
                <p class="text-muted">Check back soon for new events and trainings.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($upcomingEvents as $evt): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm event-card">
                        <?php if ($evt['image']): ?>
                            <img src="uploads/events/<?= e($evt['image']) ?>" class="card-img-top" alt="<?= e($evt['title']) ?>" style="height: 180px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="height: 180px;">
                                <i class="bi bi-calendar-event display-3 text-success"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <?= eventTypeBadge($evt['type']) ?>
                            <h5 class="card-title fw-bold"><?= e($evt['title']) ?></h5>
                            <p class="card-text text-muted small mb-1">
                                <i class="bi bi-calendar3 me-1"></i> <?= formatDate($evt['date']) ?>
                            </p>
                            <p class="card-text text-muted small">
                                <i class="bi bi-clock me-1"></i> <?= formatTime($evt['time']) ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success">
                                    <?= $evt['price'] > 0 ? formatCurrency((float)$evt['price']) : 'Free' ?>
                                </span>
                                <a href="event-detail.php?slug=<?= e($evt['slug']) ?>" class="btn btn-outline-success btn-sm">
                                    Details <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($upcomingEvents) >= 6): ?>
            <div class="text-center mt-5">
                <a href="events.php" class="btn btn-success btn-lg px-5">
                    View All Events <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
