<?php
/**
 * Admin Event Form
 *
 * Handles both creating and editing events.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo    = getDB();
$errors = [];
$event  = null;
$isEdit = false;
$action = 'Add';
$editId = (int)($_GET['edit'] ?? 0);

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$editId]);
    $event = $stmt->fetch();

    if (!$event) {
        setFlash('error', 'Event not found.');
        redirect(APP_URL . '/admin/events.php');
    }

    $isEdit = true;
    $action = 'Edit';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data = [
        'title'        => trim($_POST['title'] ?? ''),
        'description'  => trim($_POST['description'] ?? ''),
        'type'         => $_POST['type'] ?? 'online',
        'date'         => $_POST['date'] ?? '',
        'time'         => $_POST['time'] ?? '',
        'end_date'     => trim($_POST['end_date'] ?? '') ?: null,
        'end_time'     => trim($_POST['end_time'] ?? '') ?: null,
        'venue'        => trim($_POST['venue'] ?? ''),
        'meeting_link' => trim($_POST['meeting_link'] ?? ''),
        'capacity'     => max(0, (int)($_POST['capacity'] ?? 0)),
        'price'        => max(0, (float)($_POST['price'] ?? 0)),
        'status'       => $_POST['status'] ?? 'upcoming',
        'is_featured'  => isset($_POST['is_featured']) ? 1 : 0,
    ];

    // Validation
    if (empty($data['title'])) {
        $errors[] = 'Event title is required.';
    }
    if (empty($data['description'])) {
        $errors[] = 'Event description is required.';
    }
    if (empty($data['date'])) {
        $errors[] = 'Event date is required.';
    }
    if (empty($data['time'])) {
        $errors[] = 'Event time is required.';
    }
    if ($data['type'] === 'in-person' && empty($data['venue'])) {
        $errors[] = 'Venue is required for in-person events.';
    }
    if ($data['type'] === 'online' && empty($data['meeting_link'])) {
        $errors[] = 'Meeting link is required for online events.';
    }
    if ($data['type'] === 'hybrid' && (empty($data['venue']) || empty($data['meeting_link']))) {
        $errors[] = 'Both venue and meeting link are required for hybrid events.';
    }

    // Handle image upload
    $imageName = $event['image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['image'];
        $maxSize  = 2 * 1024 * 1024; // 2MB

        if ($file['size'] > $maxSize) {
            $errors[] = 'Image must be less than 2MB.';
        } else {
            $tmpName = $file['tmp_name'];
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($tmpName);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];

            if (!isset($allowed[$realMime])) {
                $errors[] = 'Image must be JPEG, PNG, GIF, or WebP.';
            } else {
                $ext = $allowed[$realMime];
                $imageName = 'event_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = EVENTS_UPLOADS_PATH . '/' . $imageName;

                if (!is_dir(EVENTS_UPLOADS_PATH)) {
                    mkdir(EVENTS_UPLOADS_PATH, 0755, true);
                }

                if (!move_uploaded_file($tmpName, $dest)) {
                    $errors[] = 'Failed to upload image.';
                    $imageName = $event['image'] ?? null;
                }
            }
        }
    }

    // Generate slug
    $slug = generateSlug($data['title']);
    if ($isEdit && $event['slug'] !== $slug) {
        $slug = $slug . '-' . $event['id'];
    }

    // Check slug uniqueness
    $slugCheck = $pdo->prepare("SELECT COUNT(*) FROM events WHERE slug = ? AND id != ?");
    $slugCheck->execute([$slug, $editId]);
    if ($slugCheck->fetchColumn() > 0) {
        $slug = $slug . '-' . bin2hex(random_bytes(3));
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                // Remove old image if changed
                if ($imageName !== ($event['image'] ?? null) && !empty($event['image'])) {
                    $oldFile = EVENTS_UPLOADS_PATH . '/' . $event['image'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE events SET
                        title = ?, slug = ?, description = ?, type = ?,
                        date = ?, time = ?, end_date = ?, end_time = ?,
                        venue = ?, meeting_link = ?, capacity = ?, price = ?,
                        image = ?, status = ?, is_featured = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['title'], $slug, $data['description'], $data['type'],
                    $data['date'], $data['time'], $data['end_date'], $data['end_time'],
                    $data['venue'], $data['meeting_link'], $data['capacity'], $data['price'],
                    $imageName, $data['status'], $data['is_featured'], $editId,
                ]);

                setFlash('success', 'Event updated successfully!');
                logAdminAction('event_update', "Updated event: {$data['title']} (ID: {$editId})");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO events (title, slug, description, type, date, time, end_date, end_time,
                                        venue, meeting_link, capacity, price, image, status, is_featured)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['title'], $slug, $data['description'], $data['type'],
                    $data['date'], $data['time'], $data['end_date'], $data['end_time'],
                    $data['venue'], $data['meeting_link'], $data['capacity'], $data['price'],
                    $imageName, $data['status'], $data['is_featured'],
                ]);

                setFlash('success', 'Event created successfully!');
                logAdminAction('event_create', "Created event: {$data['title']}");
            }

            redirect(APP_URL . '/admin/events.php');
        } catch (PDOException $e) {
            $errors[] = 'Database error. Please try again.';
            logError("Event save error", $e);
        }
    }
}

$pageTitle = $action . ' Event';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="<?= e($data['title'] ?? $event['title'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?= e($data['description'] ?? $event['description'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label for="type" class="form-label">Event Type *</label>
                            <select class="form-select" id="type" name="type" required onchange="toggleEventFields()">
                                <option value="online" <?= ($data['type'] ?? $event['type'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
                                <option value="in-person" <?= ($data['type'] ?? $event['type'] ?? '') === 'in-person' ? 'selected' : '' ?>>In-Person</option>
                                <option value="hybrid" <?= ($data['type'] ?? $event['type'] ?? '') === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="upcoming" <?= ($data['status'] ?? $event['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                <option value="ongoing" <?= ($data['status'] ?? $event['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="completed" <?= ($data['status'] ?? $event['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($data['status'] ?? $event['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                    <?= ($data['is_featured'] ?? $event['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_featured">Featured Event</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="date" class="form-label">Start Date *</label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?= $data['date'] ?? $event['date'] ?? '' ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="time" class="form-label">Start Time *</label>
                            <input type="time" class="form-control" id="time" name="time"
                                   value="<?= $data['time'] ?? $event['time'] ?? '' ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="capacity" class="form-label">Capacity (0 = unlimited)</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="0"
                                   value="<?= $data['capacity'] ?? $event['capacity'] ?? 0 ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?= $data['end_date'] ?? $event['end_date'] ?? '' ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time"
                                   value="<?= $data['end_time'] ?? $event['end_time'] ?? '' ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="price" class="form-label">Price (<?= CURRENCY ?>)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01"
                                   value="<?= $data['price'] ?? $event['price'] ?? 0 ?>" aria-describedby="priceHelp">
                            <small id="priceHelp" class="text-muted">Set to 0 for free events</small>
                        </div>

                        <div class="col-md-6" id="venueField">
                            <label for="venue" class="form-label">Venue</label>
                            <input type="text" class="form-control" id="venue" name="venue"
                                   value="<?= e($data['venue'] ?? $event['venue'] ?? '') ?>"
                                   placeholder="Physical venue address">
                        </div>

                        <div class="col-md-6" id="linkField">
                            <label for="meeting_link" class="form-label">Meeting Link</label>
                            <input type="url" class="form-control" id="meeting_link" name="meeting_link"
                                   value="<?= e($data['meeting_link'] ?? $event['meeting_link'] ?? '') ?>"
                                   placeholder="https://zoom.us/j/...">
                        </div>

                        <div class="col-12">
                            <label for="image" class="form-label">Event Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" aria-describedby="imageHelp">
                            <?php if ($isEdit && !empty($event['image'])): ?>
                                <div class="mt-2">
                                    <img src="../uploads/events/<?= e($event['image']) ?>" alt="Current image" class="img-thumbnail" style="max-height: 100px;">
                                    <small class="text-muted d-block">Current image. Upload new to replace.</small>
                                </div>
                            <?php endif; ?>
                            <small id="imageHelp" class="text-muted">Max 2MB. JPEG, PNG, GIF, WebP.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="events.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Update' : 'Create' ?> Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEventFields() {
    const type = document.getElementById('type').value;
    const venueField = document.getElementById('venueField');
    const linkField = document.getElementById('linkField');

    venueField.style.display = (type === 'in-person' || type === 'hybrid') ? 'block' : 'none';
    linkField.style.display = (type === 'online' || type === 'hybrid') ? 'block' : 'none';
}
toggleEventFields();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
