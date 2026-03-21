<?php
/**
 * Production — copy to: config.production.php on the server (gitignored)
 *
 * Use the FULL database and user names from Hostinger / cPanel (with the u123_ prefix).
 * Do not upload config.local.php to production.
 */
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'u390644174_vivek_cms');
define('DB_USER', 'u390644174_vivchoudhary');
define('DB_PASS', 'Nobitavivek@123');
define('DB_CHARSET', 'utf8mb4');

define('APP_URL', 'https://silver-dove-647325.hostingersite.com');

/** Hide errors from visitors (keep false on live site) */
define('VIVEK_DEBUG', false);