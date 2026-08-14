    </div>

    <script src="/assets/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="/assets/vendor/select2/select2.min.js"></script>
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

            $('.select2-customer').select2({
                theme: 'bootstrap-5',
                placeholder: 'Ketik nama / kode customer...',
                allowClear: true,
                ajax: {
                    url: '/api/customers',
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
