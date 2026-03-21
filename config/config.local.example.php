<?php
/**
 * Local development — copy to: config.local.php (gitignored)
 *
 * Mac: DB_HOST 127.0.0.1 avoids common socket errors. Match DB_PASS to database/local-dev-grant.sql if you use it.
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'vivek_cms');
define('DB_USER', 'vivchoudhary');
define('DB_PASS', 'change_me_local');
define('DB_CHARSET', 'utf8mb4');

define('APP_URL', 'http://127.0.0.1:9000');

/** Show PHP errors in the browser (keep true locally) */
define('VIVEK_DEBUG', true);

// Optional: Unix socket instead of TCP (uncomment if needed)
// define('DB_SOCKET', '/tmp/mysql.sock');
