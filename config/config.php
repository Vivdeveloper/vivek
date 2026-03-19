<?php
/**
 * Application Configuration
 * Update these values for your hosting environment
 */

// ============================================
// 🔧 DATABASE SETTINGS - UPDATE THESE!
// ============================================
define('DB_HOST', 'localhost'); // Usually 'localhost' on cPanel
define('DB_NAME', 'vivek_cms'); // Your database name
define('DB_USER', 'vivchoudhary'); // Your database username
define('DB_PASS', ''); // Your database password
define('DB_CHARSET', 'utf8mb4');

// ============================================
// 🌐 APP SETTINGS
// ============================================
define('APP_NAME', 'SEO Website Designer');
define('APP_URL', 'http://localhost:9000'); // Change to your domain: https://yourdomain.com
define('APP_VERSION', '1.0.0');

// ============================================
// 📁 PATH SETTINGS
// ============================================
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// ============================================
// 🔒 SECURITY SETTINGS
// ============================================
define('SESSION_NAME', 'vivek_cms_session');
define('HASH_COST', 12); // bcrypt cost factor
define('POSTS_PER_PAGE', 9);

// ============================================
// ⏰ TIMEZONE
// ============================================
date_default_timezone_set('Asia/Kolkata');

// ============================================
// 🐛 ERROR REPORTING (turn off in production)
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production