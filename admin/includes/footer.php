        </div><!-- /.admin-body -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Close sidebar on resize to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth >= 992) {
        document.getElementById('adminSidebar').classList.remove('show');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }
});
</script>
</body>
</html>
