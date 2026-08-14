<?php
$selectedCustomer = $customer ?? '';
$selectedPart     = $partNo ?? '';
$searchMode       = $selectedCustomer !== '' ? 'customer' : ($selectedPart !== '' ? 'part' : 'customer');
?>
<form method="GET" action="/trace" class="row g-3 mb-4 p-3 bg-light rounded border" id="traceForm">
    <div class="col-md-3">
        <label class="form-label fw-semibold">Cari berdasarkan</label>
        <select class="form-select" id="search_mode" name="search_mode">
            <option value="customer" <?= $searchMode === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="part" <?= $searchMode === 'part' ? 'selected' : '' ?>>Leoco Part No</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="customer" class="form-label fw-semibold">Customer</label>
        <select class="form-select select2-customer" id="customer" name="customer" style="width:100%">
            <?php if ($selectedCustomer): ?>
            <option value="<?= htmlspecialchars($selectedCustomer) ?>" selected>
                <?= htmlspecialchars($selectedCustomer) ?>
            </option>
            <?php endif; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label for="part_no" class="form-label fw-semibold">Part No (Leoco)</label>
        <select class="form-select select2" id="part_no" name="part_no" style="width:100%">
            <?php if ($selectedPart): ?>
            <option value="<?= htmlspecialchars($selectedPart) ?>" selected>
                <?= htmlspecialchars($selectedPart) ?>
            </option>
            <?php endif; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label for="date_from" class="form-label fw-semibold">Dari Tanggal Kirim</label>
        <input type="date" class="form-control" id="date_from" name="date_from"
               value="<?= htmlspecialchars($dateFrom) ?>">
    </div>
    <div class="col-md-3">
        <label for="date_to" class="form-label fw-semibold">Sampai Tanggal Kirim</label>
        <input type="date" class="form-control" id="date_to" name="date_to"
               value="<?= htmlspecialchars($dateTo) ?>">
    </div>
    <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-primary flex-fill">Cari</button>
        <a href="/trace" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<script>
$(document).ready(function() {
    function toggleFields() {
        var mode = $('#search_mode').val();
        $('#customer').closest('.col-md-4').first().toggle(mode === 'customer');
        $('#part_no').closest('.col-md-4').first().toggle(mode === 'part');
    }
    $('#search_mode').on('change', toggleFields);
    toggleFields();
});
</script>
