    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Ketik untuk cari Part No...',
                allowClear: true,
                ajax: {
                    url: '/api/parts',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                language: {
                    inputTooShort: function() {
                        return 'Ketik minimal 1 karakter';
                    },
                    searching: function() {
                        return 'Mencari...';
                    },
                    noResults: function() {
                        return 'Tidak ditemukan';
                    }
                }
            });
        });
    </script>
</body>
</html>
