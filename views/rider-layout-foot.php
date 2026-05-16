<?php require __DIR__ . '/rider-nav.php'; ?>
<script>
if (typeof kkRiderStatusChange === 'undefined') {
    function kkRiderStatusChange(sel) {
        if (sel.value === 'delivered') {
            window.location.href = sel.getAttribute('data-complete-url') || 'complete-delivery.php';
            return;
        }
        sel.form.submit();
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/pwa-script.php'; ?>
</body>
</html>
