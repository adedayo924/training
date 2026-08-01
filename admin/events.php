<?php
/**
 * Admin Events List
 *
 * Displays all events with search, filter, and pagination.
 */

$pageTitle = 'Events';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// Filters
$search    = trim($_GET['search'] ?? '');
$status    = $_GET['status'] ?? '';
$type      = $_GET['type'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 15;
$offset    = ($page - 1) * $perPage;

// Build query
$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(e.title LIKE ? OR e.description LIKE ?)";
    $escapedSearch = '%' . escapeLike($search) . '%';
    $params[] = $escapedSearch;
    $params[] = $escapedSearch;
}
if ($status !== '' && in_array($status, ['upcoming', 'ongoing', 'completed', 'cancelled'])) {
    $where[]  = "e.status = ?";
    $params[] = $status;
}
if ($type !== '' && in_array($type, ['online', 'in-person', 'hybrid'])) {
    $where[]  = "e.type = ?";
    $params[] = $type;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countSQL = "SELECT COUNT(*) FROM events e {$whereSQL}";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch events
$sql = "SELECT e.*, (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as reg_count
        FROM events e {$whereSQL}
        ORDER BY e.date DESC, e.time DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Total: <?= $totalRows ?> event<?= $totalRows !== 1 ? 's' : '' ?></p>
    </div>
    <a href="event-form.php" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i> Add Event
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search events..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                    <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="type">
                    <option value="">All Types</option>
                    <option value="online" <?= $type === 'online' ? 'selected' : '' ?>>Online</option>
                    <option value="in-person" <?= $type === 'in-person' ? 'selected' : '' ?>>In-Person</option>
                    <option value="hybrid" <?= $type === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-success w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Events Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($events)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                No events found.
                <br><a href="event-form.php" class="btn btn-success btn-sm mt-2">Create First Event</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Price</th>
                            <th>Regs</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $evt): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($evt['title']) ?></div>
                                <?php if ($evt['image']): ?>
                                    <small class="text-muted"><i class="bi bi-image me-1"></i>Has image</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= eventTypeBadge($evt['type']) ?>
                            </td>
                            <td>
                                <div><?= formatDate($evt['date']) ?></div>
                                <small class="text-muted"><?= formatTime($evt['time']) ?></small>
                            </td>
                            <td>
                                <?= $evt['price'] > 0 ? formatCurrency((float)$evt['price']) : '<span class="text-success">Free</span>' ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= $evt['reg_count'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?= eventStatusClass($evt['status']) ?>"><?= ucfirst($evt['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($evt['is_featured']): ?>
                                    <i class="bi bi-star-fill text-warning"></i>
                                <?php else: ?>
                                    <i class="bi bi-star text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="event-form.php?edit=<?= $evt['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="../event-detail.php?slug=<?= e($evt['slug']) ?>" target="_blank" class="btn btn-outline-secondary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" action="event-delete.php" class="d-inline btn-delete-form" data-name="<?= e($evt['title']) ?>">
                                        <input type="hidden" name="id" value="<?= $evt['id'] ?>">
                                        <?= csrfField() ?>
                                        <button type="submit" class="btn btn-outline-danger btn-delete" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?>
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $queryParams = $_GET;
                        for ($i = 1; $i <= $totalPages; $i++):
                            $queryParams['page'] = $i;
                            $url = '?' . http_build_query($queryParams);
                        ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $url ?>" aria-label="Page <?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var form = this.closest('.btn-delete-form');
        var name = form ? form.dataset.name : 'this item';
        if (!confirm('Are you sure you want to delete "' + name + '"? This will also delete all registrations for this event.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
