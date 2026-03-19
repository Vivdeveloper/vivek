<?php
require_once __DIR__ . '/includes/functions.php';

try {
    $db = db();
    
    // 1. Create custom_post_types table
    $db->exec("
        CREATE TABLE IF NOT EXISTS custom_post_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) NOT NULL UNIQUE,
            icon VARCHAR(50) DEFAULT 'fas fa-file-alt',
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Create custom_fields table
    $db->exec("
        CREATE TABLE IF NOT EXISTS custom_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_type VARCHAR(50) NOT NULL,
            field_name VARCHAR(50) NOT NULL,
            field_label VARCHAR(100) NOT NULL,
            field_type ENUM('text', 'textarea', 'image', 'number', 'boolean', 'select') DEFAULT 'text',
            sort_order INT DEFAULT 0,
            options TEXT NULL, -- comma separated if field_type is select
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_field (post_type, field_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Create custom_field_values table
    $db->exec("
        CREATE TABLE IF NOT EXISTS custom_field_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            field_id INT NOT NULL,
            value TEXT,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE,
            UNIQUE KEY unique_post_field (post_id, field_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Update posts table for CPTs and SEO
    
    // Check if post_type column exists
    $columns = $db->query("SHOW COLUMNS FROM posts LIKE 'post_type'")->fetchAll();
    if (empty($columns)) {
        $db->exec("ALTER TABLE posts ADD COLUMN post_type VARCHAR(50) DEFAULT 'post' AFTER id");
        $db->exec("CREATE INDEX idx_post_type ON posts(post_type)");
    }

    // Check if meta_title exists
    $columns = $db->query("SHOW COLUMNS FROM posts LIKE 'meta_title'")->fetchAll();
    if (empty($columns)) {
        $db->exec("ALTER TABLE posts ADD COLUMN meta_title VARCHAR(255) NULL");
        $db->exec("ALTER TABLE posts ADD COLUMN meta_description TEXT NULL");
        $db->exec("ALTER TABLE posts ADD COLUMN focus_keyword VARCHAR(100) NULL");
    }

    echo "✅ Database Migrations completed successfully! Ready for CPT & Custom Fields.";
} catch (PDOException $e) {
    echo "❌ Migration Error: " . $e->getMessage();
}
