<form method="GET" class="row g-3 mb-4 p-3 bg-light rounded border" id="filterForm">
    <div class="col-md-3">
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
        <label for="wo_no" class="form-label fw-semibold">WO No</label>
        <select class="form-select select2-wo" id="wo_no" name="wo_no" style="width:100%">
            <?php if (!empty($selectedWo)): ?>
            <option value="<?= htmlspecialchars($selectedWo) ?>" selected>
                <?= htmlspecialchars($selectedWo) ?>
            </option>
            <?php endif; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label for="date_from" class="form-label fw-semibold">Dari Tanggal</label>
        <input type="date" class="form-control" id="date_from" name="date_from"
               value="<?= htmlspecialchars($dateFrom) ?>">
    </div>
    <div class="col-md-2">
        <label for="date_to" class="form-label fw-semibold">Sampai Tanggal</label>
        <input type="date" class="form-control" id="date_to" name="date_to"
               value="<?= htmlspecialchars($dateTo) ?>">
    </div>
    <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-primary flex-fill">Cari</button>
        <a href="/" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>
