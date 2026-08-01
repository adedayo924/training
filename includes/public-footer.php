</main>

<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-calendar-event-fill me-2 text-success"></i><?= e(APP_NAME) ?>
                </h5>
                <p class="text-white-50">
                    Your premier platform for professional events, webinars, and training programs.
                    Register for upcoming events and advance your career.
                </p>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= e(APP_URL) ?>" class="text-white-50 text-decoration-none"><i class="bi bi-chevron-right me-1"></i> Home</a></li>
                    <li class="mb-2"><a href="<?= e(APP_URL) ?>/events.php" class="text-white-50 text-decoration-none"><i class="bi bi-chevron-right me-1"></i> All Events</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-bold mb-3">Contact Us</h6>
                <ul class="list-unstyled text-white-50">
                    <li class="mb-2"><i class="bi bi-envelope me-2"></i> <?= e(getSetting('site_email', 'info@jelocdw.com')) ?></li>
                    <li class="mb-2"><i class="bi bi-telephone me-2"></i> <?= e(getSetting('site_phone', '+234 XXX XXX XXXX')) ?></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <small class="text-white-50">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-white-50">Powered by <strong class="text-success"><?= e(APP_NAME) ?></strong></small>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
