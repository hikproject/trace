<?php

namespace App;

class Database
{
    private static $conn = null;

    public static function connect()
    {
        if (self::$conn !== null) return self::$conn;

        $connStr = sprintf(
            '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SID=%s)))',
            DB_HOST,
            DB_PORT,
            DB_SID
        );

        self::$conn = oci_connect(DB_USER, DB_PASS, $connStr, DB_CHARSET);

        if (!self::$conn) {
            $e = oci_error();
            throw new \RuntimeException('Oracle connection failed: ' . ($e['message'] ?? 'unknown error'));
        }

        return self::$conn;
    }

    public static function close()
    {
        if (self::$conn) {
            oci_close(self::$conn);
            self::$conn = null;
        }
    }
}
