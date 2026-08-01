<?php
/**
 * Admin Event Delete
 *
 * POST-only handler for deleting events.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(APP_URL . '/admin/events.php');
}

requireCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid event ID.');
    redirect(APP_URL . '/admin/events.php');
}

try {
    $pdo = getDB();

    // Get event to delete image file
    $stmt = $pdo->prepare("SELECT image FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        setFlash('error', 'Event not found.');
        redirect(APP_URL . '/admin/events.php');
    }

    // Delete event (registrations cascade due to FK)
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);

    // Delete image file
    if (!empty($event['image'])) {
        $imagePath = EVENTS_UPLOADS_PATH . '/' . $event['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    setFlash('success', 'Event deleted successfully.');
    logAdminAction('event_delete', "Deleted event ID: {$id}");
} catch (PDOException $e) {
    setFlash('error', 'Failed to delete event. Please try again.');
    logError("Event delete error for ID: {$id}", $e);
}

redirect(APP_URL . '/admin/events.php');
