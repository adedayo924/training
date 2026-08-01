<?php
/**
 * Export Registrations to CSV
 *
 * Exports filtered registrations as a downloadable CSV file.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// Same filters as registrations.php
$search      = trim($_GET['search'] ?? '');
$eventFilter = (int)($_GET['event_id'] ?? 0);
$status      = $_GET['payment_status'] ?? '';

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

$sql = "SELECT r.full_name, r.email, r.phone, e.title as event_title, e.date as event_date,
               r.payment_status, r.amount_paid, r.paystack_reference, r.registered_at
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        {$whereSQL}
        ORDER BY r.registered_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Set CSV headers
$filename = 'registrations_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header row
fputcsv($output, [
    'Full Name',
    'Email',
    'Phone',
    'Event',
    'Event Date',
    'Payment Status',
    'Amount Paid (' . CURRENCY . ')',
    'Paystack Reference',
    'Registration Date',
]);

// Stream data rows one at a time to avoid memory exhaustion
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['event_title'],
        date('M d, Y', strtotime($row['event_date'])),
        ucfirst($row['payment_status']),
        number_format($row['amount_paid'], 2),
        $row['paystack_reference'] ?: 'N/A',
        date('M d, Y h:i A', strtotime($row['registered_at'])),
    ]);
}

fclose($output);
exit;
