        </div><!-- /.content -->
    </div><!-- /.main -->
</div><!-- /.app-shell -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        if (!toggle || !sidebar) return;

        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('is-collapsed');
        });
    });
</script>
</body>
</html>
