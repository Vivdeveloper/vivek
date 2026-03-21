<?php
/**
 * PDO MySQL DSN builder.
 * Requires config/config.php to be loaded first (defines DB_* constants).
 */
function vivek_mysql_dsn(bool $includeDbName = true): string {
    if (defined('DB_SOCKET') && DB_SOCKET !== '') {
        $dsn = 'mysql:unix_socket=' . DB_SOCKET . ';charset=' . DB_CHARSET;
    } else {
        $port = defined('DB_PORT') ? (int) DB_PORT : 3306;
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ';charset=' . DB_CHARSET;
    }
    if ($includeDbName) {
        $dsn .= ';dbname=' . DB_NAME;
    }
    return $dsn;
}
