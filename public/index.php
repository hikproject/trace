<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\Database;
use App\Part;
use App\Report;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ──────────────────────────────────────────────
// Route: /api/parts — Select2 AJAX autocomplete
// ──────────────────────────────────────────────
if ($uri === '/api/parts') {
    header('Content-Type: application/json');
    $keyword = $_GET['q'] ?? '';
    try {
        $results = Part::search($keyword);
        echo json_encode($results);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    } finally {
        Database::close();
    }
    exit;
}

// ──────────────────────────────────────────────
// Route: /export-excel — Download Excel
// ──────────────────────────────────────────────
if ($uri === '/export-excel') {
    $partNo   = $_GET['part_no'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo   = $_GET['date_to']   ?? '';

    if (!$partNo || !$dateFrom || !$dateTo) {
        http_response_code(400);
        echo 'Parameter tidak lengkap';
        exit;
    }

    try {
        $grouped = Report::getAllData($partNo, $dateFrom, $dateTo);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo 'Error: ' . $e->getMessage();
        exit;
    } finally {
        Database::close();
    }

    if (empty($grouped)) {
        http_response_code(404);
        echo 'Tidak ada data untuk part number tersebut.';
        exit;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Report');

    // Total kolom sekarang menjadi 18 (A sampai R)
    $maxCol = 18; 
    $rowNum = 1;

    foreach ($grouped as $wo) {
        // ───────────────── ROW 1: WO Header ─────────────────
        $woLabel = sprintf(
            'WO: %s | Customer: %s | Part: %s, %s | Cust PN: %s | Prod Qty: %s',
            $wo['no_wo'], 
            $wo['customer_name'], 
            $wo['leoco_pn'] ?? '', 
            $wo['part_name'],
            $wo['cust_pn'], 
            number_format((float)($wo['production_qty'] ?? 0))
        );
        $sheet->setCellValueByColumnAndRow(1, $rowNum, $woLabel);
        $sheet->mergeCellsByColumnAndRow(1, $rowNum, $maxCol, $rowNum);
        $sheet->getStyleByColumnAndRow(1, $rowNum, $maxCol, $rowNum)
            ->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
        $sheet->getStyleByColumnAndRow(1, $rowNum, $maxCol, $rowNum)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('3B63C2'); // Biru
        $rowNum++;

        // ───────────────── ROW 2: Header Kategori ─────────────────
        // Kolom 1 (WO) kosong di baris ini
        
        // Kategori: ISSUING MATERIAL (Kolom 2 - 9)
        $sheet->setCellValueByColumnAndRow(2, $rowNum, 'ISSUING MATERIAL');
        $sheet->mergeCellsByColumnAndRow(2, $rowNum, 9, $rowNum);
        $sheet->getStyleByColumnAndRow(2, $rowNum, 9, $rowNum)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('00B0F0'); // Biru Muda
        $sheet->getStyleByColumnAndRow(2, $rowNum, 9, $rowNum)
            ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

        // Kategori: FINAL CHECK & DC IN (Kolom 10 - 15)
        $sheet->setCellValueByColumnAndRow(10, $rowNum, 'FINAL CHECK & DC IN');
        $sheet->mergeCellsByColumnAndRow(10, $rowNum, 15, $rowNum);
        $sheet->getStyleByColumnAndRow(10, $rowNum, 15, $rowNum)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002060'); // Biru Tua / Navy
        $sheet->getStyleByColumnAndRow(10, $rowNum, 15, $rowNum)
            ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

        // Kategori: BARANG OUT (Kolom 16 - 18)
        $sheet->setCellValueByColumnAndRow(16, $rowNum, 'BARANG OUT');
        $sheet->mergeCellsByColumnAndRow(16, $rowNum, 18, $rowNum);
        $sheet->getStyleByColumnAndRow(16, $rowNum, 18, $rowNum)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('ED7D31'); // Oranye
        $sheet->getStyleByColumnAndRow(16, $rowNum, 18, $rowNum)
            ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $rowNum++;

        // ───────────────── ROW 3: Header Kolom ─────────────────
        $headers = [
            'WO', 
            // Issuing
            'Issuing No', 'Tgl Issuing', 'Item No', 'Item Name', 'Lot No', 'Spec', 'Unit', 'Qty', 
            // FQC & DC IN
            'FQC No', 'Tgl FQC', 'Q Sent', 'Stock In No', 'Stock In Date', 'Stock In Qty', 
            // Barang Out
            'DO No', 'Tgl Kirim', 'Qty'
        ];
        
        foreach ($headers as $ci => $h) {
            $sheet->setCellValueByColumnAndRow($ci + 1, $rowNum, $h);
        }
        $sheet->getStyleByColumnAndRow(1, $rowNum, $maxCol, $rowNum)
            ->getFont()->setBold(true);
        $sheet->getStyleByColumnAndRow(1, $rowNum, $maxCol, $rowNum)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F0F0F0'); // Abu-abu muda
        $rowNum++;

        // ───────────────── DATA ROWS ─────────────────
        $issuingData = $wo['issuing'] ?? [];
        $fqcData     = $wo['details'] ?? [];
        $barangOut   = $wo['barang_out'] ?? [];

        // Cari jumlah baris terbanyak
        $maxDataRows = max(count($issuingData), count($fqcData), count($barangOut));
        if ($maxDataRows == 0) $maxDataRows = 1; // Minimal print 1 baris kosong jika tidak ada data sama sekali

        for ($i = 0; $i < $maxDataRows; $i++) {
            $iss = $issuingData[$i] ?? null;
            $det = $fqcData[$i] ?? null;
            $bo  = $barangOut[$i] ?? null;

            // Kolom 1: Selalu tampilkan WO (berulang sesuai gambar)
            $sheet->setCellValueByColumnAndRow(1, $rowNum, $wo['no_wo']);

            // Kolom 2-9: Issuing
            if ($iss) {
                $sheet->setCellValueByColumnAndRow(2, $rowNum, $iss['issuing_no']);
                $sheet->setCellValueByColumnAndRow(3, $rowNum, $iss['issuing_date']);
                $sheet->setCellValueByColumnAndRow(4, $rowNum, $iss['item_no']);
                $sheet->setCellValueByColumnAndRow(5, $rowNum, $iss['item_name']);
                $sheet->setCellValueByColumnAndRow(6, $rowNum, $iss['lot_no']);
                $sheet->setCellValueByColumnAndRow(7, $rowNum, $iss['spec']);
                $sheet->setCellValueByColumnAndRow(8, $rowNum, $iss['unit']);
                $sheet->setCellValueByColumnAndRow(9, $rowNum, $iss['issuing_qty']);
            }

            // Kolom 10-15: FQC & DC IN
            if ($det) {
                $sheet->setCellValueByColumnAndRow(10, $rowNum, $det['fqc_no']);
                $sheet->setCellValueByColumnAndRow(11, $rowNum, $det['tdate_fqc']);
                $sheet->setCellValueByColumnAndRow(12, $rowNum, $det['qsent']);
                $sheet->setCellValueByColumnAndRow(13, $rowNum, $det['stock_in_no']);
                $sheet->setCellValueByColumnAndRow(14, $rowNum, $det['stock_in_date']);
                $sheet->setCellValueByColumnAndRow(15, $rowNum, $det['stock_in_qty']);
            }

            // Kolom 16-18: Barang Out
            if ($bo) {
                $sheet->setCellValueByColumnAndRow(16, $rowNum, $bo['do_no']);
                $sheet->setCellValueByColumnAndRow(17, $rowNum, $bo['tgl_kirim']);
                $sheet->setCellValueByColumnAndRow(18, $rowNum, $bo['do_qty']);
            }
            
            $rowNum++;
        }
        
        // (Opsional) Tambahkan jarak 1 baris antar WO jika diinginkan.
        // $rowNum++; 
    }

    // Auto-size kolom A sampai R agar rapi
    $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R'];
    foreach ($columns as $colLetter) {
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="report_leoco_' . $partNo . '_' . $dateFrom . '_' . $dateTo . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ──────────────────────────────────────────────
// Route: / — Main page (form + report)
// ──────────────────────────────────────────────
$partNo   = $_GET['part_no'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
$page     = max(1, (int) ($_GET['page'] ?? 1));

$report = null;
$selectedPart = $partNo;

if ($partNo && $dateFrom && $dateTo) {
    try {
        $report = Report::getData($partNo, $dateFrom, $dateTo, $page);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    } finally {
        Database::close();
    }
}

require __DIR__ . '/../views/header.php';
require __DIR__ . '/../views/form.php';
if (isset($error)) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($error) . '</div>';
}
require __DIR__ . '/../views/report.php';
require __DIR__ . '/../views/footer.php';
