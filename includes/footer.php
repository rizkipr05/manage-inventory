    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Fungsi untuk format mata uang Indonesia
        function formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }
        
        // Konfirmasi sebelum delete
        function confirmDelete(id, nama) {
            if (confirm('Yakin ingin menghapus ' + nama + '?')) {
                return true;
            }
            return false;
        }

        // Auto refresh halaman (simulasi realtime) jika data-refresh > 0
        (function () {
            var refreshSeconds = parseInt(document.body.getAttribute('data-refresh') || '0', 10);
            if (refreshSeconds > 0) {
                setInterval(function () {
                    window.location.reload();
                }, refreshSeconds * 1000);
            }
        })();
    </script>
</body>
</html>
