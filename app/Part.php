<?php

namespace App;

class Part
{
    public static function search($keyword)
    {
        $conn = Database::connect();

        $sql = "SELECT * FROM (
                    SELECT DISTINCT sfb05
                    FROM sfb_file
                    WHERE sfb05 LIKE :q
                    ORDER BY sfb05
                ) WHERE ROWNUM <= 20";

        $stmt = oci_parse($conn, $sql);
        $q = '%' . $keyword . '%';
        oci_bind_by_name($stmt, ':q', $q);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = [
                'id' => $row['SFB05'],
                'text' => $row['SFB05']
            ];
        }

        oci_free_statement($stmt);
        return $results;
    }
}
