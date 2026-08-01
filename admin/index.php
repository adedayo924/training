<?php
/**
 * Admin Dashboard
 *
 * Shows overview statistics and recent registrations.
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// All stats in a single query
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM events) as total_events,
        (SELECT COUNT(*) FROM events WHERE status = 'upcoming') as upcoming_events,
        (SELECT COUNT(*) FROM registrations) as total_registrations,
        (SELECT COUNT(*) FROM registrations WHERE payment_status = 'paid') as paid_registrations,
        (SELECT COALESCE(SUM(amount_paid), 0) FROM registrations WHERE payment_status = 'paid') as total_revenue,
        (SELECT COUNT(*) FROM registrations WHERE DATE(registered_at) = CURDATE()) as today_registrations
")->fetch();

$totalEvents = $stats['total_events'];
$upcomingEvents = $stats['upcoming_events'];
$totalRegistrations = $stats['total_registrations'];
$paidRegistrations = $stats['paid_registrations'];
$totalRevenue = $stats['total_revenue'];
$todayRegistrations = $stats['today_registrations'];

// Recent 10 registrations
$recentStmt = $pdo->query("
    SELECT r.*, e.title as event_title
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    ORDER BY r.registered_at DESC
    LIMIT 10
");
$recentRegistrations = $recentStmt->fetchAll();

// Upcoming 5 events with registration counts
$regCounts = [];
$regCountsStmt = $pdo->query("SELECT event_id, COUNT(*) as cnt FROM registrations GROUP BY event_id");
while ($row = $regCountsStmt->fetch()) {
    $regCounts[$row['event_id']] = $row['cnt'];
}

$upcomingStmt = $pdo->query("
    SELECT e.*
    FROM events e
    WHERE e.status = 'upcoming' AND e.date >= CURDATE()
    ORDER BY e.date ASC
    LIMIT 5
");
$upcomingList = $upcomingStmt->fetchAll();
foreach ($upcomingList as &$evt) {
    $evt['reg_count'] = $regCounts[$evt['id']] ?? 0;
}
unset($evt);

require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Total Events</p>
                        <h3 class="fw-bold mb-0"><?= $totalEvents ?></h3>
                        <small class="text-success"><?= $upcomingEvents ?> upcoming</small>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-calendar3 text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Total Registrations</p>
                        <h3 class="fw-bold mb-0"><?= $totalRegistrations ?></h3>
                        <small class="text-info"><?= $todayRegistrations ?> today</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-people text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Paid Registrations</p>
                        <h3 class="fw-bold mb-0"><?= $paidRegistrations ?></h3>
                        <small class="text-muted">of <?= $totalRegistrations ?> total</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-credit-card text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1">Total Revenue</p>
                        <h3 class="fw-bold mb-0"><?= formatCurrency((float)$totalRevenue) ?></h3>
                        <small class="text-success">from paid events</small>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-currency-dollar text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Registrations -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-success"></i>Recent Registrations</h5>
                <a href="registrations.php" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentRegistrations)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No registrations yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRegistrations as $reg): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($reg['full_name']) ?></td>
                                    <td><?= e($reg['email']) ?></td>
                                    <td><?= e($reg['event_title']) ?></td>
                                    <td>
                                        <?= paymentStatusBadge($reg['payment_status']) ?>
                                    </td>
                                    <td class="text-muted small"><?= timeElapsed($reg['registered_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Events -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2 text-success"></i>Upcoming Events</h5>
                <a href="events.php" class="btn btn-sm btn-outline-success">Manage</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcomingList)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        No upcoming events.
                        <br><a href="event-form.php" class="btn btn-success btn-sm mt-2">Create Event</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingList as $evt): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-semibold"><?= e($evt['title']) ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?= formatDate($evt['date']) ?>
                                        at <?= formatTime($evt['time']) ?>
                                    </small>
                                    <br>
                                    <small>
                                        <?= eventTypeBadge($evt['type']) ?>
                                        <span class="text-muted ms-1">
                                            <i class="bi bi-people me-1"></i><?= $evt['reg_count'] ?> registered
                                        </span>
                                    </small>
                                </div>
                                <a href="event-form.php?edit=<?= $evt['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
