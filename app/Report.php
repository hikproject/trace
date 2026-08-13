<?php

namespace App;

class Report
{
    public static function getData($partNo, $dateFrom, $dateTo, $page = 1, $perPage = 20)
    {
        $conn = Database::connect();
        $offset = ($page - 1) * $perPage;

        // 1. Count total distinct WOs
        $countSql = "SELECT COUNT(DISTINCT sfb01) AS total 
                     FROM sfb_file 
                     WHERE sfb05 = :part_no 
                       AND sfb81 BETWEEN TO_DATE(:dt_from, 'YYYY-MM-DD') 
                                     AND TO_DATE(:dt_to, 'YYYY-MM-DD')";

        $stmt = oci_parse($conn, $countSql);
        oci_bind_by_name($stmt, ':part_no', $partNo);
        oci_bind_by_name($stmt, ':dt_from', $dateFrom);
        oci_bind_by_name($stmt, ':dt_to', $dateTo);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        $total = (int) $row['TOTAL'];
        oci_free_statement($stmt);

        // 2. Get paginated WO list
        $woSql = "SELECT sfb01 FROM (
                    SELECT sfb01, ROWNUM rnum FROM (
                        SELECT DISTINCT sfb01 
                        FROM sfb_file 
                        WHERE sfb05 = :part_no2 
                          AND sfb81 BETWEEN TO_DATE(:dt_from2, 'YYYY-MM-DD') 
                                        AND TO_DATE(:dt_to2, 'YYYY-MM-DD')
                        ORDER BY sfb01 DESC
                    ) WHERE ROWNUM <= :max_row
                  ) WHERE rnum > :offset";

        $stmt = oci_parse($conn, $woSql);
        $maxRow = $offset + $perPage;
        oci_bind_by_name($stmt, ':part_no2', $partNo);
        oci_bind_by_name($stmt, ':dt_from2', $dateFrom);
        oci_bind_by_name($stmt, ':dt_to2', $dateTo);
        oci_bind_by_name($stmt, ':max_row', $maxRow);
        oci_bind_by_name($stmt, ':offset', $offset);
        oci_execute($stmt);

        $woList = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $woList[] = trim($row['SFB01']);
        }
        oci_free_statement($stmt);

        if (empty($woList)) {
            return [
                'data' => [],
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        }

        // 3. Build IN clause dynamically
        $bindParams = [];
        $placeholders = [];
        foreach ($woList as $i => $wo) {
            $key = ":wo_{$i}";
            $placeholders[] = $key;
            $bindParams[$key] = $wo;
        }
        $inClause = implode(',', $placeholders);

        // 4. Get details for those WOs (FQC & DC In)
        $detailSql = "SELECT 
                        occ.occ02 AS customer_name,
                        TRIM(x.xmf01) AS code_customer,
                        TRIM(i.ima021) AS cust_pn,
                        TRIM(sfb.sfb05) AS leoco_pn,
                        i.ima02 AS part_name,
                        TRIM(sfb.sfb01) AS no_wo,
                        sfb.sfb25 AS tanggal_produksi,
                        sfb.sfb81 AS tanggal_wo_buat,
                        sfb.sfb08 AS production_qty,
                        TRIM(qcf.qcf01) AS fqc_no,
                        qcf.qcf04 AS tdate_fqc,
                        qcf.qcf22 AS qsent,
                        TRIM(sfu.sfu01) AS stock_in_no,
                        sfu.sfu02 AS stock_in_date,
                        sfv.sfv09 AS stock_in_qty
                      FROM sfb_file sfb
                      LEFT JOIN ima_file i ON TRIM(sfb.sfb05) = TRIM(i.ima01)
                      LEFT JOIN xmf_file x ON TRIM(sfb.sfb05) = TRIM(x.xmf03)
                      LEFT JOIN occ_file occ ON TRIM(x.xmf01) = TRIM(occ.occ01)
                      LEFT JOIN qcf_file qcf ON TRIM(sfb.sfb01) = TRIM(qcf.qcf02)
                      LEFT JOIN sfv_file sfv ON TRIM(qcf.qcf01) = TRIM(sfv.sfv17)
                      LEFT JOIN sfu_file sfu ON TRIM(sfv.sfv01) = TRIM(sfu.sfu01)
                      WHERE TRIM(sfb.sfb01) IN ($inClause)
                      ORDER BY sfb.sfb01 DESC, qcf.qcf01";

        $stmt = oci_parse($conn, $detailSql);
        foreach ($bindParams as $key => &$val) {
            oci_bind_by_name($stmt, $key, $val);
        }
        unset($val);
        oci_execute($stmt);

        $flatData = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $flatData[] = $row;
        }
        oci_free_statement($stmt);

        // 5. Get Barang Out data (DO)
        $outSql = "SELECT 
                        TRIM(oga.oga01) AS do_no,
                        oga.oga02 AS tgl_kirim,
                        TRIM(ogb.ogb092) AS no_wo,
                        ogb.ogb12 AS do_qty
                   FROM ogb_file ogb
                   LEFT JOIN oga_file oga ON TRIM(ogb.ogb01) = TRIM(oga.oga01)
                   WHERE TRIM(ogb.ogb092) IN ($inClause)
                   ORDER BY ogb.ogb092, oga.oga01";

        $outStmt = oci_parse($conn, $outSql);
        foreach ($bindParams as $key => &$val) {
            oci_bind_by_name($outStmt, $key, $val);
        }
        unset($val);
        oci_execute($outStmt);

        $outData = [];
        while ($row = oci_fetch_assoc($outStmt)) {
            $outData[] = $row;
        }
        oci_free_statement($outStmt);

        // 6. Get Issuing Material data (dengan item detail dari sfs/sfe/imn)
        $issSql = "SELECT 
                        TRIM(sfq.sfq02) AS no_wo,
                        TRIM(sfq.sfq01) AS issuing_no,
                        sfp.sfpdate     AS issuing_date,
                        TRIM(d.item_no) AS item_no,
                        i.ima02         AS item_name,
                        TRIM(d.lot_no)  AS lot_no,
                        i.ima021        AS spec,
                        TRIM(d.unit)    AS unit,
                        NVL(d.issuing_qty, 0) AS issuing_qty
                   FROM sfq_file sfq
                   LEFT JOIN sfp_file sfp ON TRIM(sfq.sfq01) = TRIM(sfp.sfp01)
                   LEFT JOIN (
                        SELECT doc_no, item_no, lot_no, MAX(unit) AS unit, SUM(issuing_qty) AS issuing_qty
                        FROM (
                            -- Type 3 via sfs_file
                            SELECT sfp01 AS doc_no, sfs04 AS item_no, sfs09 AS lot_no,
                                   sfs06 AS unit, sfs05 AS issuing_qty
                            FROM sfp_file
                            INNER JOIN leocoid.sfs_file ON sfp01 = sfs01
                            WHERE sfp06 IN ('1','2','3') AND NVL(sfpconf, ' ') <> 'X'
                            UNION ALL
                            -- Type 3 via sfe_file
                           SELECT sfp01 AS doc_no, sfe07 AS item_no, sfe10 AS lot_no,
                                    sfe17 AS unit, sfe16 AS issuing_qty
                             FROM sfp_file
                             INNER JOIN leocoid.sfe_file ON sfp01 = sfe02
                             WHERE sfp06 IN ('1','2','3') AND NVL(sfpconf, ' ') <> 'X' AND sfe06 IN ('1','2','3')
                             UNION ALL
                             -- Type 4 via imn_file
                            SELECT imm01 AS doc_no, imn03 AS item_no, imn06 AS lot_no,
                                   NULL AS unit, imn10 AS issuing_qty
                            FROM leocoid.imm_file
                            INNER JOIN leocoid.imn_file ON imm01 = imn01
                        )
                        GROUP BY doc_no, item_no, lot_no
                    ) d ON TRIM(sfq.sfq01) = d.doc_no
                   LEFT JOIN ima_file i ON TRIM(d.item_no) = TRIM(i.ima01)
                   WHERE TRIM(sfq.sfq02) IN ($inClause)
                   ORDER BY sfq.sfq02, sfq.sfq01, d.item_no";

        $issStmt = oci_parse($conn, $issSql);
        foreach ($bindParams as $key => &$val) {
            oci_bind_by_name($issStmt, $key, $val);
        }
        unset($val);
        oci_execute($issStmt);

        $issData = [];
        while ($row = oci_fetch_assoc($issStmt)) {
            $issData[] = $row;
        }
        oci_free_statement($issStmt);

        // 7. Grouping final result by WO number
        $grouped = [];
        foreach ($flatData as $row) {
            $wo = $row['NO_WO'];
            if (!isset($grouped[$wo])) {
                $grouped[$wo] = [
                    'code_customer' => $row['CODE_CUSTOMER'] ?? '',
                    'customer_name' => $row['CUSTOMER_NAME'] ?? '',
                    'cust_pn' => $row['CUST_PN'] ?? '',
                    'leoco_pn' => $row['LEOCO_PN'] ?? '',
                    'part_name' => $row['PART_NAME'] ?? '',
                    'no_wo' => $row['NO_WO'] ?? '',
                    'tanggal_produksi' => $row['TANGGAL_PRODUKSI'] ?? '',
                    'tanggal_wo_buat' => $row['TANGGAL_WO_BUAT'] ?? '',
                    'production_qty' => $row['PRODUCTION_QTY'] ?? '',
                    'details' => [],
                    'barang_out' => [],
                    'issuing' => []
                ];
            }
            if ($row['FQC_NO'] !== null) {
                $fqcKey = $row['FQC_NO'] . '_' . $row['STOCK_IN_NO'];
                $grouped[$wo]['details'][$fqcKey] = [
                    'fqc_no' => $row['FQC_NO'] ?? '',
                    'tdate_fqc' => $row['TDATE_FQC'] ?? '',
                    'qsent' => $row['QSENT'] ?? '',
                    'stock_in_no' => $row['STOCK_IN_NO'] ?? '',
                    'stock_in_date' => $row['STOCK_IN_DATE'] ?? '',
                    'stock_in_qty' => $row['STOCK_IN_QTY'] ?? ''
                ];
            }
        }

        foreach ($grouped as $wo => $data) {
            $grouped[$wo]['details'] = array_values($data['details']);
        }

        foreach ($outData as $row) {
            $wo = $row['NO_WO'];
            if (isset($grouped[$wo])) {
                $grouped[$wo]['barang_out'][] = [
                    'do_no' => $row['DO_NO'] ?? '',
                    'tgl_kirim' => $row['TGL_KIRIM'] ?? '',
                    'do_qty' => $row['DO_QTY'] ?? ''
                ];
            }
        }

        foreach ($issData as $row) {
            $wo = $row['NO_WO'];
            if (isset($grouped[$wo])) {
                $grouped[$wo]['issuing'][] = [
                    'issuing_no' => $row['ISSUING_NO'] ?? '',
                    'issuing_date' => $row['ISSUING_DATE'] ?? '',
                    'item_no' => $row['ITEM_NO'] ?? '',
                    'item_name' => $row['ITEM_NAME'] ?? '',
                    'lot_no' => $row['LOT_NO'] ?? '',
                    'spec' => $row['SPEC'] ?? '',
                    'unit' => $row['UNIT'] ?? '',
                    'issuing_qty' => $row['ISSUING_QTY'] ?? ''
                ];
            }
        }

        return [
            'data' => array_values($grouped),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    public static function getAllData($partNo, $dateFrom, $dateTo)
    {
        $conn = Database::connect();

        $woSql = "SELECT DISTINCT sfb01 
                  FROM sfb_file 
                  WHERE sfb05 = :part_no 
                    AND sfb81 BETWEEN TO_DATE(:dt_from, 'YYYY-MM-DD') 
                                  AND TO_DATE(:dt_to, 'YYYY-MM-DD')
                  ORDER BY sfb01 DESC";

        $stmt = oci_parse($conn, $woSql);
        oci_bind_by_name($stmt, ':part_no', $partNo);
        oci_bind_by_name($stmt, ':dt_from', $dateFrom);
        oci_bind_by_name($stmt, ':dt_to', $dateTo);
        oci_execute($stmt);

        $woList = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $woList[] = trim($row['SFB01']);
        }
        oci_free_statement($stmt);

        if (empty($woList)) return [];

        $bindParams = [];
        $placeholders = [];
        foreach ($woList as $i => $wo) {
            $key = ":wo_{$i}";
            $placeholders[] = $key;
            $bindParams[$key] = $wo;
        }
        $inClause = implode(',', $placeholders);

        $detailSql = "SELECT 
                        occ.occ02 AS customer_name,
                        TRIM(x.xmf01) AS code_customer,
                        TRIM(i.ima021) AS cust_pn,
                        TRIM(sfb.sfb05) AS leoco_pn,
                        i.ima02 AS part_name,
                        TRIM(sfb.sfb01) AS no_wo,
                        sfb.sfb25 AS tanggal_produksi,
                        sfb.sfb81 AS tanggal_wo_buat,
                        sfb.sfb08 AS production_qty,
                        TRIM(qcf.qcf01) AS fqc_no,
                        qcf.qcf04 AS tdate_fqc,
                        qcf.qcf22 AS qsent,
                        TRIM(sfu.sfu01) AS stock_in_no,
                        sfu.sfu02 AS stock_in_date,
                        sfv.sfv09 AS stock_in_qty
                      FROM sfb_file sfb
                      LEFT JOIN ima_file i ON TRIM(sfb.sfb05) = TRIM(i.ima01)
                      LEFT JOIN xmf_file x ON TRIM(sfb.sfb05) = TRIM(x.xmf03)
                      LEFT JOIN occ_file occ ON TRIM(x.xmf01) = TRIM(occ.occ01)
                      LEFT JOIN qcf_file qcf ON TRIM(sfb.sfb01) = TRIM(qcf.qcf02)
                      LEFT JOIN sfv_file sfv ON TRIM(qcf.qcf01) = TRIM(sfv.sfv17)
                      LEFT JOIN sfu_file sfu ON TRIM(sfv.sfv01) = TRIM(sfu.sfu01)
                      WHERE TRIM(sfb.sfb01) IN ($inClause)
                      ORDER BY sfb.sfb01 DESC, qcf.qcf01";

        $stmt = oci_parse($conn, $detailSql);
        foreach ($bindParams as $key => &$val) {
            oci_bind_by_name($stmt, $key, $val);
        }
        unset($val);
        oci_execute($stmt);

        $flatData = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $flatData[] = $row;
        }
        oci_free_statement($stmt);

        $outSql = "SELECT 
                        TRIM(oga.oga01) AS do_no,
                        oga.oga02 AS tgl_kirim,
                        TRIM(ogb.ogb092) AS no_wo,
                        ogb.ogb12 AS do_qty
                   FROM ogb_file ogb
                   LEFT JOIN oga_file oga ON TRIM(ogb.ogb01) = TRIM(oga.oga01)
                   WHERE TRIM(ogb.ogb092) IN ($inClause)
                   ORDER BY ogb.ogb092, oga.oga01";

        $outStmt = oci_parse($conn, $outSql);
        foreach ($bindParams as $key => &$val) {
            oci_bind_by_name($outStmt, $key, $val);
        }
        unset($val);
        oci_execute($outStmt);

        $outData = [];
        while ($row = oci_fetch_assoc($outStmt)) {
            $outData[] = $row;
        }
        oci_free_statement($outStmt);

        $issSql = "SELECT 
                        TRIM(sfq.sfq02) AS no_wo,
                        TRIM(sfq.sfq01) AS issuing_no,
                        sfp.sfpdate     AS issuing_date,
                        TRIM(d.item_no) AS item_no,
                        i.ima02         AS item_name,
                        TRIM(d.lot_no)  AS lot_no,
                        i.ima021        AS spec,
                        TRIM(d.unit)    AS unit,
                        NVL(d.issuing_qty, 0) AS issuing_qty
                   FROM sfq_file sfq
                   LEFT JOIN sfp_file sfp ON TRIM(sfq.sfq01) = TRIM(sfp.sfp01)
                   LEFT JOIN (
                        SELECT doc_no, item_no, lot_no, MAX(unit) AS unit, SUM(issuing_qty) AS issuing_qty
                        FROM (
                            SELECT sfp01 AS doc_no, sfs04 AS item_no, sfs09 AS lot_no,
                                   sfs06 AS unit, sfs05 AS issuing_qty
                            FROM sfp_file
                            INNER JOIN leocoid.sfs_file ON sfp01 = sfs01
                            WHERE sfp06 IN ('1','2','3') AND NVL(sfpconf, ' ') <> 'X'
                             UNION ALL
                             SELECT sfp01 AS doc_no, sfe07 AS item_no, sfe10 AS lot_no,
                                    sfe17 AS unit, sfe16 AS issuing_qty
                             FROM sfp_file
                             INNER JOIN leocoid.sfe_file ON sfp01 = sfe02
                             WHERE sfp06 IN ('1','2','3') AND NVL(sfpconf, ' ') <> 'X' AND sfe06 IN ('1','2','3')
                             UNION ALL
                             SELECT imm01 AS doc_no, imn03 AS item_no, imn06 AS lot_no,
                                   NULL AS unit, imn10 AS issuing_qty
                            FROM leocoid.imm_file
                            INNER JOIN leocoid.imn_file ON imm01 = imn01
                        )
                        GROUP BY doc_no, item_no, lot_no
                   ) d ON TRIM(sfq.sfq01) = d.doc_no
                   LEFT JOIN ima_file i ON TRIM(d.item_no) = TRIM(i.ima01)
                   WHERE TRIM(sfq.sfq02) IN ($inClause)
                   ORDER BY sfq.sfq02, sfq.sfq01, d.item_no";

        $issStmt = oci_parse($conn, $issSql);
        foreach ($bindParams as $key => &$val) {
            oci_bind_by_name($issStmt, $key, $val);
        }
        unset($val);
        oci_execute($issStmt);

        $issData = [];
        while ($row = oci_fetch_assoc($issStmt)) {
            $issData[] = $row;
        }
        oci_free_statement($issStmt);

        $grouped = [];
        foreach ($flatData as $row) {
            $wo = $row['NO_WO'];
            if (!isset($grouped[$wo])) {
                $grouped[$wo] = [
                    'code_customer' => $row['CODE_CUSTOMER'] ?? '',
                    'customer_name' => $row['CUSTOMER_NAME'] ?? '',
                    'cust_pn' => $row['CUST_PN'] ?? '',
                    'leoco_pn' => $row['LEOCO_PN'] ?? '',
                    'part_name' => $row['PART_NAME'] ?? '',
                    'no_wo' => $row['NO_WO'] ?? '',
                    'tanggal_produksi' => $row['TANGGAL_PRODUKSI'] ?? '',
                    'tanggal_wo_buat' => $row['TANGGAL_WO_BUAT'] ?? '',
                    'production_qty' => $row['PRODUCTION_QTY'] ?? '',
                    'details' => [],
                    'barang_out' => [],
                    'issuing' => []
                ];
            }
            if ($row['FQC_NO'] !== null) {
                $fqcKey = $row['FQC_NO'] . '_' . $row['STOCK_IN_NO'];
                $grouped[$wo]['details'][$fqcKey] = [
                    'fqc_no' => $row['FQC_NO'] ?? '',
                    'tdate_fqc' => $row['TDATE_FQC'] ?? '',
                    'qsent' => $row['QSENT'] ?? '',
                    'stock_in_no' => $row['STOCK_IN_NO'] ?? '',
                    'stock_in_date' => $row['STOCK_IN_DATE'] ?? '',
                    'stock_in_qty' => $row['STOCK_IN_QTY'] ?? ''
                ];
            }
        }

        foreach ($grouped as $wo => $data) {
            $grouped[$wo]['details'] = array_values($data['details']);
        }

        foreach ($outData as $row) {
            $wo = $row['NO_WO'];
            if (isset($grouped[$wo])) {
                $grouped[$wo]['barang_out'][] = [
                    'do_no' => $row['DO_NO'] ?? '',
                    'tgl_kirim' => $row['TGL_KIRIM'] ?? '',
                    'do_qty' => $row['DO_QTY'] ?? ''
                ];
            }
        }

        foreach ($issData as $row) {
            $wo = $row['NO_WO'];
            if (isset($grouped[$wo])) {
                $grouped[$wo]['issuing'][] = [
                    'issuing_no' => $row['ISSUING_NO'] ?? '',
                    'issuing_date' => $row['ISSUING_DATE'] ?? '',
                    'item_no' => $row['ITEM_NO'] ?? '',
                    'item_name' => $row['ITEM_NAME'] ?? '',
                    'lot_no' => $row['LOT_NO'] ?? '',
                    'spec' => $row['SPEC'] ?? '',
                    'unit' => $row['UNIT'] ?? '',
                    'issuing_qty' => $row['ISSUING_QTY'] ?? ''
                ];
            }
        }

        return array_values($grouped);
    }
}