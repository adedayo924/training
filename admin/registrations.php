<?php
/**
 * Admin Registrations List
 *
 * Shows all registrations with filtering and search.
 */

$pageTitle = 'Registrations';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// Filters
$search      = trim($_GET['search'] ?? '');
$eventFilter = (int)($_GET['event_id'] ?? 0);
$status      = $_GET['payment_status'] ?? '';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 20;
$offset      = ($page - 1) * $perPage;

// Build query
$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(r.full_name LIKE ? OR r.email LIKE ? OR r.phone LIKE ?)";
    $escapedSearch = '%' . escapeLike($search) . '%';
    $params[] = $escapedSearch;
    $params[] = $escapedSearch;
    $params[] = $escapedSearch;
}
if ($eventFilter > 0) {
    $where[]  = "r.event_id = ?";
    $params[] = $eventFilter;
}
if ($status !== '' && in_array($status, ['free', 'pending', 'paid', 'failed'])) {
    $where[]  = "r.payment_status = ?";
    $params[] = $status;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countSQL  = "SELECT COUNT(*) FROM registrations r {$whereSQL}";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalRows  = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch
$sql = "SELECT r.*, e.title as event_title, e.date as event_date, e.price as event_price
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        {$whereSQL}
        ORDER BY r.registered_at DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Get events for filter dropdown
$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY title ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Total: <?= $totalRows ?> registration<?= $totalRows !== 1 ? 's' : '' ?></p>
    </div>
    <div>
        <form method="GET" class="d-inline" id="exportForm">
            <input type="hidden" name="search" value="<?= e($search) ?>">
            <input type="hidden" name="event_id" value="<?= $eventFilter ?>">
            <input type="hidden" name="payment_status" value="<?= e($status) ?>">
            <button type="submit" formaction="export-csv.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i> Export CSV
            </button>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Search name, email, phone..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="event_id">
                    <option value="">All Events</option>
                    <?php foreach ($eventsList as $evt): ?>
                        <option value="<?= $evt['id'] ?>" <?= $eventFilter === (int)$evt['id'] ? 'selected' : '' ?>>
                            <?= e($evt['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="payment_status">
                    <option value="">All Status</option>
                    <option value="free" <?= $status === 'free' ? 'selected' : '' ?>>Free</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-success w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Registrations Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($registrations)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No registrations found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Event</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Registered</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $i => $reg): ?>
                        <tr>
                            <td class="text-muted"><?= $offset + $i + 1 ?></td>
                            <td class="fw-semibold"><?= e($reg['full_name']) ?></td>
                            <td><?= e($reg['email']) ?></td>
                            <td><?= e($reg['phone']) ?></td>
                            <td>
                                <small><?= e($reg['event_title']) ?></small>
                                <br><small class="text-muted"><?= formatDate($reg['event_date']) ?></small>
                            </td>
                            <td><?= $reg['amount_paid'] > 0 ? formatCurrency((float)$reg['amount_paid']) : '-' ?></td>
                            <td>
                                <?= paymentStatusBadge($reg['payment_status']) ?>
                            </td>
                            <td class="text-muted small"><?= timeElapsed($reg['registered_at']) ?></td>
                            <td>
                                <a href="registration-detail.php?id=<?= $reg['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
