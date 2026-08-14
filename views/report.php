<?php if ($report && $report['total'] > 0): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Total WO: <?= number_format($report['total']) ?></h5>
    <a href="/export-excel?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>" class="btn btn-success">Export Excel</a>
</div>

<?php $idx = 0; foreach ($report['data'] as $wo): $idx++; ?>
<div class="card mb-2 border">
    <div class="card-header bg-light p-0" role="button" aria-expanded="false">
        <div class="w-100 m-0 py-1 px-3">
            <div class="row gx-2" style="font-size:11px;color:#6c757d;">
                <div class="col-auto" style="width:28px"></div>
                <div class="col-1">WO No</div>
                <div class="col-1">Code Cust</div>
                <div class="col-1">Nama Cust</div>
                <div class="col-1">Cust PN</div>
                <div class="col-1">Leoco PN</div>
                <div class="col-1">Part Name</div>
                <div class="col-1">Tgl WO Buat</div>
                <div class="col-1">Tgl Prod</div>
                <div class="col-1 text-end">Prod Qty</div>
            </div>
            <div class="row gx-2 align-items-center">
                <div class="col-auto" style="width:28px">
                    <span class="toggle-icon">▶</span>
                </div>
                <div class="col-1 text-truncate fw-semibold" title="<?= htmlspecialchars(trim($wo['no_wo'])) ?>"><?= htmlspecialchars(trim($wo['no_wo'])) ?></div>
                <div class="col-1 text-truncate" title="<?= htmlspecialchars(trim($wo['code_customer'])) ?>"><?= htmlspecialchars(trim($wo['code_customer'])) ?></div>
                <div class="col-1 text-truncate" title="<?= htmlspecialchars(trim($wo['customer_name'])) ?>"><?= htmlspecialchars(trim($wo['customer_name'])) ?></div>
                <div class="col-1 text-truncate" title="<?= htmlspecialchars(trim($wo['cust_pn'])) ?>"><?= htmlspecialchars(trim($wo['cust_pn'])) ?></div>
                <div class="col-1 text-truncate" title="<?= htmlspecialchars(trim($wo['leoco_pn'])) ?>"><?= htmlspecialchars(trim($wo['leoco_pn'])) ?></div>
                <div class="col-1 text-truncate" title="<?= htmlspecialchars(trim($wo['part_name'])) ?>"><?= htmlspecialchars(trim($wo['part_name'])) ?></div>
                <div class="col-1 text-truncate"><?= htmlspecialchars(trim($wo['tanggal_wo_buat'])) ?></div>
                <div class="col-1 text-truncate"><?= htmlspecialchars(trim($wo['tanggal_produksi'])) ?></div>
                <div class="col-1 text-end fw-semibold"><?= number_format($wo['production_qty']) ?></div>
            </div>
        </div>
    </div>
    <div id="woDetail<?= $idx ?>" class="collapse">
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                <thead>
                    <tr>
                        <th colspan="8" class="bg-info text-white fw-bold py-1">Issuing Material</th>
                    </tr>
                    <tr class="table-secondary">
                        <th>Issuing No</th>
                        <th>Tgl Issuing</th>
                        <th>Item No</th>
                        <th>Item Name</th>
                        <th>Lot No</th>
                        <th>Spec</th>
                        <th>Unit</th>
                        <th class="text-end">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($wo['issuing'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-2">Tidak ada data Issuing Material</td></tr>
                    <?php else: ?>
                    <?php foreach ($wo['issuing'] as $iss): ?>
                    <tr>
                        <td style="background:#e0f2fe"><?= htmlspecialchars(trim($iss['issuing_no'])) ?></td>
                        <td style="background:#e0f2fe"><?= htmlspecialchars(trim($iss['issuing_date'])) ?></td>
                        <td style="background:#e0f2fe"><?= htmlspecialchars(trim($iss['item_no'])) ?></td>
                        <td style="background:#e0f2fe"><?= htmlspecialchars(trim($iss['item_name'])) ?></td>
                        <td style="background:#e0f2fe"><?= htmlspecialchars(trim($iss['lot_no'])) ?></td>
                        <td style="background:#e0f2fe"><?= htmlspecialchars(trim($iss['spec'])) ?></td>
                        <td style="background:#e0f2fe" class="text-center"><?= htmlspecialchars(trim($iss['unit'])) ?></td>
                        <td class="text-end" style="background:#e0f2fe"><?= number_format($iss['issuing_qty']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <table class="table table-sm table-bordered mb-0 border-top" style="font-size:12px;">
                <thead>
                    <tr>
                        <th colspan="3" class="bg-primary text-white fw-bold py-1">Final Check</th>
                        <th colspan="3" class="bg-success text-white fw-bold py-1">DC In</th>
                    </tr>
                    <tr class="table-secondary">
                        <th>FQC No</th>
                        <th>Tgl Final Check</th>
                        <th class="text-end">Q Sent</th>
                        <th>No</th>
                        <th>Tgl</th>
                        <th class="text-end">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($wo['details'])): ?>
                    <tr><td colspan="6" class="text-center text-muted py-2">Tidak ada data Final Check / DC In</td></tr>
                    <?php else: ?>
                    <?php foreach ($wo['details'] as $det): ?>
                    <tr>
                        <td style="background:#e3f2fd"><?= htmlspecialchars(trim($det['fqc_no'])) ?></td>
                        <td style="background:#e3f2fd"><?= htmlspecialchars(trim($det['tdate_fqc'])) ?></td>
                        <td class="text-end" style="background:#e3f2fd"><?= number_format($det['qsent']) ?></td>
                        <td style="background:#e8f5e9"><?= htmlspecialchars(trim($det['stock_in_no'])) ?></td>
                        <td style="background:#e8f5e9"><?= htmlspecialchars(trim($det['stock_in_date'])) ?></td>
                        <td class="text-end" style="background:#e8f5e9"><?= number_format($det['stock_in_qty']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <table class="table table-sm table-bordered mb-0 border-top" style="font-size:12px;">
                <thead>
                    <tr>
                        <th colspan="3" class="bg-warning text-dark fw-bold py-1">Barang Out</th>
                    </tr>
                    <tr class="table-secondary">
                        <th>DO No</th>
                        <th>Tgl Kirim</th>
                        <th class="text-end">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($wo['barang_out'])): ?>
                    <tr><td colspan="3" class="text-center text-muted py-2">Tidak ada data Barang Out</td></tr>
                    <?php else: ?>
                    <?php foreach ($wo['barang_out'] as $bo): ?>
                    <tr>
                        <td style="background:#fff3e0"><?= htmlspecialchars(trim($bo['do_no'])) ?></td>
                        <td style="background:#fff3e0"><?= htmlspecialchars(trim($bo['tgl_kirim'])) ?></td>
                        <td class="text-end" style="background:#fff3e0"><?= number_format($bo['do_qty']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.card-header[role="button"]').forEach(function(header) {
    header.addEventListener('click', function() {
        var collapse = header.nextElementSibling;
        if (!collapse || !collapse.classList.contains('collapse')) return;
        var icon = header.querySelector('.toggle-icon');
        var isShown = collapse.classList.toggle('show');
        if (icon) icon.textContent = isShown ? '▼' : '▶';
        header.setAttribute('aria-expanded', isShown ? 'true' : 'false');
    });
});
</script>

<?php if ($report['totalPages'] > 1): ?>
<?php
$currentPage = $report['page'];
$totalPages = $report['totalPages'];
$queryParams = $_GET;
unset($queryParams['page']);
$baseQuery = http_build_query($queryParams);
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $baseQuery ?>&page=<?= $currentPage - 1 ?>">Previous</a>
        </li>
        <?php
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?<?= $baseQuery ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $baseQuery ?>&page=<?= $currentPage + 1 ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['part_no'])): ?>
<div class="alert alert-info mt-3">Tidak ada WO ditemukan untuk part number tersebut.</div>
<?php endif; ?>