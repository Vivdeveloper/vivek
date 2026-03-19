<?php
require_once dirname(__DIR__) . '/includes/functions.php';
try {
    // Check if column exists
    $stmt = db()->query("SHOW COLUMNS FROM users LIKE 'permissions'");
    if (!$stmt->fetch()) {
        db()->query("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT NULL");
        echo "Column 'permissions' added to 'users' table successfully.";
    } else {
        echo "Column 'permissions' already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
