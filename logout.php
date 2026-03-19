<?php
require_once __DIR__ . '/includes/functions.php';
startSecureSession();
session_destroy();
header("Location: " . APP_URL . "/");
exit;
