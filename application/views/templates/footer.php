    </div><!-- /.app-content -->

    <div class="app-footer">
        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
    </div>

<script>
(function () {
    // Navbar (.app-sidebar) sekarang bisa jadi 1 baris atau lebih (wrap),
    // jadi jarak konten (.app-content) di bawahnya dihitung ulang setiap kali
    // ukuran layar berubah, bukan pakai angka tetap.
    function layoutOffset() {
        var header = document.querySelector('.app-header');
        var sidebar = document.querySelector('.app-sidebar');
        var content = document.querySelector('.app-content');
        if (!header || !sidebar || !content) return;

        var headerH = header.offsetHeight;
        sidebar.style.top = headerH + 'px';

        var totalH = headerH + sidebar.offsetHeight;
        content.style.paddingTop = (totalH + 14) + 'px';
    }

    document.addEventListener('DOMContentLoaded', layoutOffset);
    window.addEventListener('load', layoutOffset);
    window.addEventListener('resize', layoutOffset);
})();
</script>
</body>
</html>
