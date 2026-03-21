-- Run as a MySQL admin (e.g. `sudo mysql` on Mac Homebrew) if you get
-- "Access denied" (1698) or your hosting username does not exist locally.
--
-- Pick a strong password for local use; match DB_PASS in config/config.local.php

CREATE DATABASE IF NOT EXISTS vivek_cms
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'vivchoudhary'@'127.0.0.1' IDENTIFIED BY 'change_me_local';
CREATE USER IF NOT EXISTS 'vivchoudhary'@'localhost' IDENTIFIED BY 'change_me_local';
-- IF NOT EXISTS does not update password; force password if user already existed:
ALTER USER 'vivchoudhary'@'127.0.0.1' IDENTIFIED BY 'change_me_local';
ALTER USER 'vivchoudhary'@'localhost' IDENTIFIED BY 'change_me_local';

GRANT ALL PRIVILEGES ON vivek_cms.* TO 'vivchoudhary'@'127.0.0.1';
GRANT ALL PRIVILEGES ON vivek_cms.* TO 'vivchoudhary'@'localhost';

FLUSH PRIVILEGES;
