<?php

namespace App;

class Trace
{
    public static function customers($keyword)
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '') return [];
        $keyword = mb_substr($keyword, 0, 50);

        $conn = Database::connect();

        $sql = "SELECT * FROM (
                    SELECT DISTINCT TRIM(occ01) AS code, TRIM(occ02) AS name
                    FROM occ_file
                    WHERE UPPER(occ02) LIKE UPPER(:q) OR UPPER(occ01) LIKE UPPER(:q)
                    ORDER BY name
                ) WHERE ROWNUM <= 20";

        $stmt = oci_parse($conn, $sql);
        $q = '%' . $keyword . '%';
        oci_bind_by_name($stmt, ':q', $q);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = [
                'id' => trim($row['CODE']),
                'text' => trim($row['CODE']) . ' - ' . trim($row['NAME'])
            ];
        }

        oci_free_statement($stmt);
        return $results;
    }

    private static function buildFilter($customer, $partNo, $dateFrom, $dateTo)
    {
        $conds = [];
        $binds = [];

        if ($customer !== '') {
            $conds[] = "TRIM(ogb.ogb04) IN (SELECT TRIM(xmf03) FROM xmf_file WHERE TRIM(xmf01) = :cust)";
            $binds[':cust'] = $customer;
        } elseif ($partNo !== '') {
            $conds[] = "TRIM(ogb.ogb04) = :part_no";
            $binds[':part_no'] = $partNo;
        } else {
            return null;
        }

        $dtFrom = $dateFrom !== '' ? $dateFrom : '1900-01-01';
        $dtTo   = $dateTo !== '' ? $dateTo : '2100-12-31';
        $conds[] = "oga.oga02 BETWEEN TO_DATE(:dt_from, 'YYYY-MM-DD') AND TO_DATE(:dt_to, 'YYYY-MM-DD')";
        $binds[':dt_from'] = $dtFrom;
        $binds[':dt_to'] = $dtTo;

        return ['where' => implode(' AND ', $conds), 'binds' => $binds];
    }

    private static function bindAll($stmt, &$binds)
    {
        foreach ($binds as $key => &$val) {
            oci_bind_by_name($stmt, $key, $val);
        }
        unset($val);
    }

    public static function getData($customer, $partNo, $dateFrom, $dateTo, $page = 1, $perPage = 20)
    {
        $conn = Database::connect();
        $filter = self::buildFilter($customer, $partNo, $dateFrom, $dateTo);

        if ($filter === null) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => 0
            ];
        }

        $where = $filter['where'];
        $binds = $filter['binds'];
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) AS total
                     FROM ogb_file ogb
                     LEFT JOIN oga_file oga ON TRIM(ogb.ogb01) = TRIM(oga.oga01)
                     WHERE $where";

        $stmt = oci_parse($conn, $countSql);
        self::bindAll($stmt, $binds);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        $total = (int) $row['TOTAL'];
        oci_free_statement($stmt);

        $dataSql = "SELECT * FROM (
                        SELECT t.*, ROWNUM rnum FROM (
                            SELECT TRIM(oga.oga01) AS no_do,
                                   oga.oga02 AS tgl_kirim,
                                   TRIM(ogb.ogb092) AS lot_wo,
                                   TRIM(ogb.ogb04) AS leoco_pn,
                                   ogb.ogb06 AS part_name,
                                   ogb.ogb12 AS qty
                            FROM ogb_file ogb
                            LEFT JOIN oga_file oga ON TRIM(ogb.ogb01) = TRIM(oga.oga01)
                            WHERE $where
                            ORDER BY oga.oga02 DESC, oga.oga01, ogb.ogb03
                        ) t
                        WHERE ROWNUM <= :max_row
                    ) WHERE rnum > :offset";

        $stmt = oci_parse($conn, $dataSql);
        self::bindAll($stmt, $binds);
        $maxRow = $offset + $perPage;
        oci_bind_by_name($stmt, ':max_row', $maxRow);
        oci_bind_by_name($stmt, ':offset', $offset);
        oci_execute($stmt);

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = [
                'no_do' => $row['NO_DO'] ?? '',
                'tgl_kirim' => $row['TGL_KIRIM'] ?? '',
                'lot_wo' => $row['LOT_WO'] ?? '',
                'leoco_pn' => $row['LEOCO_PN'] ?? '',
                'part_name' => $row['PART_NAME'] ?? '',
                'qty' => $row['QTY'] ?? 0
            ];
        }
        oci_free_statement($stmt);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage)
        ];
    }

    public static function getAllData($customer, $partNo, $dateFrom, $dateTo)
    {
        $conn = Database::connect();
        $filter = self::buildFilter($customer, $partNo, $dateFrom, $dateTo);

        if ($filter === null) return [];

        $where = $filter['where'];
        $binds = $filter['binds'];

        $sql = "SELECT TRIM(oga.oga01) AS no_do,
                       oga.oga02 AS tgl_kirim,
                       TRIM(ogb.ogb092) AS lot_wo,
                       TRIM(ogb.ogb04) AS leoco_pn,
                       ogb.ogb06 AS part_name,
                       ogb.ogb12 AS qty
                FROM ogb_file ogb
                LEFT JOIN oga_file oga ON TRIM(ogb.ogb01) = TRIM(oga.oga01)
                WHERE $where
                ORDER BY oga.oga02 DESC, oga.oga01, ogb.ogb03";

        $stmt = oci_parse($conn, $sql);
        self::bindAll($stmt, $binds);
        oci_execute($stmt);

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = [
                'no_do' => $row['NO_DO'] ?? '',
                'tgl_kirim' => $row['TGL_KIRIM'] ?? '',
                'lot_wo' => $row['LOT_WO'] ?? '',
                'leoco_pn' => $row['LEOCO_PN'] ?? '',
                'part_name' => $row['PART_NAME'] ?? '',
                'qty' => $row['QTY'] ?? 0
            ];
        }
        oci_free_statement($stmt);

        return $data;
    }
}
