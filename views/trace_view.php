<?php if ($trace && $trace['total'] > 0): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Total Pengiriman: <?= number_format($trace['total']) ?></h5>
    <a href="/export-trace?<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>" class="btn btn-success">Export Excel</a>
</div>

<div class="table-responsive">
    <table class="table table-sm table-bordered table-striped align-middle" style="font-size:12px;">
        <thead class="table-secondary">
            <tr>
                <th>No DO</th>
                <th>Tanggal Kirim</th>
                <th>Lot (Kode Produksi/WO)</th>
                <th>Leoco PN</th>
                <th>Nama Barang</th>
                <th class="text-end">Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trace['data'] as $r): ?>
            <tr>
                <td><?= htmlspecialchars(trim($r['no_do'])) ?></td>
                <td><?= htmlspecialchars(trim($r['tgl_kirim'])) ?></td>
                <td>
                    <?php $lotWo = trim($r['lot_wo']); ?>
                    <?php if ($lotWo !== ''): ?>
                    <a href="/?wo_no=<?= urlencode($lotWo) ?>" target="_blank" title="Buka detail WO ini">
                        <?= htmlspecialchars($lotWo) ?>
                    </a>
                    <?php else: ?>
                    &nbsp;
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(trim($r['leoco_pn'])) ?></td>
                <td><?= htmlspecialchars(trim($r['part_name'])) ?></td>
                <td class="text-end"><?= number_format((float) $r['qty']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($trace['totalPages'] > 1): ?>
<?php
$currentPage = $trace['page'];
$totalPages = $trace['totalPages'];
$queryParams = $_GET;
unset($queryParams['page']);
$baseQuery = http_build_query($queryParams);
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="/trace?<?= $baseQuery ?>&page=<?= $currentPage - 1 ?>">Previous</a>
        </li>
        <?php
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="/trace?<?= $baseQuery ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="/trace?<?= $baseQuery ?>&page=<?= $currentPage + 1 ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php elseif ($searchMode): ?>
<div class="alert alert-info mt-3">Tidak ada pengiriman ditemukan.</div>
<?php endif; ?>
