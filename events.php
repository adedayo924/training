<?php
/**
 * Public Events Listing
 *
 * Shows all upcoming events with pagination.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;

$totalRows = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming' AND date >= CURDATE()")->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$regCounts = [];
$regCountsStmt = $pdo->query("SELECT event_id, COUNT(*) as cnt FROM registrations GROUP BY event_id");
while ($row = $regCountsStmt->fetch()) {
    $regCounts[$row['event_id']] = $row['cnt'];
}

$stmt = $pdo->prepare("
    SELECT e.*
    FROM events e
    WHERE e.status = 'upcoming' AND e.date >= CURDATE()
    ORDER BY e.is_featured DESC, e.date ASC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute();
$events = $stmt->fetchAll();
foreach ($events as &$evt) {
    $evt['reg_count'] = $regCounts[$evt['id']] ?? 0;
}
unset($evt);

$pageTitle = 'Events';
require_once __DIR__ . '/includes/public-header.php';
?>

<!-- Page Header -->
<section class="bg-success text-white py-5">
    <div class="container">
        <h1 class="fw-bold mb-2">Upcoming Events</h1>
        <p class="mb-0 opacity-75">Browse and register for our upcoming events and trainings</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (empty($events)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x display-1 text-muted" aria-hidden="true"></i>
                <h4 class="mt-3 text-muted">No upcoming events</h4>
                <p class="text-muted">Check back soon for new events and training programs.</p>
                <a href="<?= e(APP_URL) ?>" class="btn btn-success mt-2">Back to Home</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($events as $evt): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm event-card">
                        <?php if ($evt['image']): ?>
                            <img src="uploads/events/<?= e($evt['image']) ?>" class="card-img-top" alt="<?= e($evt['title']) ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-calendar-event display-3 text-success" aria-hidden="true"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="d-flex gap-2 mb-2">
                                <?= eventTypeBadge($evt['type']) ?>
                                <?php if ($evt['is_featured']): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-star-fill" aria-hidden="true"></i></span>
                                <?php endif; ?>
                            </div>
                            <h5 class="card-title fw-bold"><?= e($evt['title']) ?></h5>
                            <p class="card-text text-muted small mb-1">
                                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i> <?= formatDate($evt['date']) ?>
                            </p>
                            <p class="card-text text-muted small mb-1">
                                <i class="bi bi-clock me-1"></i> <?= formatTime($evt['time']) ?>
                            </p>
                            <p class="card-text text-muted small">
                                <i class="bi bi-people me-1"></i> <?= $evt['reg_count'] ?> registered
                            </p>
                            <?php if ($evt['venue']): ?>
                                <p class="card-text text-muted small">
                                    <i class="bi bi-geo-alt me-1"></i> <?= e($evt['venue']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success">
                                    <?= $evt['price'] > 0 ? formatCurrency((float)$evt['price']) : 'Free' ?>
                                </span>
                                <a href="event-detail.php?slug=<?= e($evt['slug']) ?>" class="btn btn-success btn-sm">
                                    Register <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>" aria-label="Page <?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
