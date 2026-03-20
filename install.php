<?php
/**
 * Installation Script
 * Run this ONCE to create all database tables and seed initial data
 * Access: yourdomain.com/install.php
 * DELETE THIS FILE AFTER INSTALLATION!
 */

require_once __DIR__ . '/config/config.php';
if (!defined('APP_NAME')) define('APP_NAME', 'VivFramework');

$results = [];
$errors = [];

if (isset($_POST['install'])) {
    try {
        // Connect to MySQL (without database)
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        $results[] = "✅ Database '" . DB_NAME . "' created";

        // Create Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'editor', 'user') DEFAULT 'user',
                avatar VARCHAR(255),
                is_blocked TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✅ Users table created";

        // Create Categories table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✅ Categories table created";

        // Create Posts table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                content LONGTEXT,
                featured_image VARCHAR(255),
                category_id INT,
                author_id INT NOT NULL,
                status ENUM('draft', 'published') DEFAULT 'draft',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✅ Posts table created";

        // Create Comments table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT,
                author_name VARCHAR(100),
                author_email VARCHAR(150),
                comment TEXT NOT NULL,
                status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✅ Comments table created";

        // Create Pages table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                content LONGTEXT,
                featured_image VARCHAR(255),
                custom_css LONGTEXT,
                status ENUM('draft', 'published') DEFAULT 'published',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✅ Pages table created";

        // Create Media table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS media (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                filepath VARCHAR(255) NOT NULL,
                mimetype VARCHAR(100),
                size INT,
                uploaded_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✅ Media table created";

        // Create Settings table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✅ Settings table created";

        // Seed Admin User
        $adminEmail = $_POST['admin_email'] ?? 'admin@example.com';
        $adminPassword = $_POST['admin_password'] ?? 'admin123';
        $adminName = $_POST['admin_name'] ?? 'Admin';
        $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$adminEmail]);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$adminName, $adminEmail, $hashedPassword]);
            $results[] = "✅ Admin user created";
        } else {
            $results[] = "ℹ️ Admin user already exists";
        }

        // Seed Categories
        $categories = [
            ['Technology', 'technology'],
            ['Lifestyle', 'lifestyle'],
            ['Business', 'business'],
            ['Health', 'health'],
            ['Travel', 'travel']
        ];
        foreach ($categories as [$name, $slug]) {
            try {
                $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")->execute([$name, $slug]);
            } catch (PDOException $e) { /* already exists */ }
        }
        $results[] = "✅ Default categories created";

        // Seed Pages
        $pages = [
            ['About Us', 'about', '<h2>About Us</h2><p>Welcome to our website. We are passionate about sharing knowledge and creating valuable content for our readers.</p><p>Our mission is to provide high-quality articles and resources that help you stay informed and inspired.</p>'],
            ['Contact', 'contact', '<h2>Contact Us</h2><p>We would love to hear from you! Feel free to reach out to us:</p><ul><li>Email: contact@example.com</li><li>Phone: +91 123 456 7890</li></ul>'],
            ['Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy outlines how we collect, use, and protect your personal information.</p>']
        ];
        foreach ($pages as [$title, $slug, $content]) {
            try {
                $pdo->prepare("INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, 'published')")->execute([$title, $slug, $content]);
            } catch (PDOException $e) { /* already exists */ }
        }
        $results[] = "✅ Default pages created";

        // Seed Sample Posts
        $samplePosts = [
            ['Getting Started with Web Development', 'getting-started-with-web-development', '<p>Web development is an exciting field that combines creativity with technical skills.</p><h3>Key Technologies</h3><ul><li><strong>HTML</strong> - The backbone of any website</li><li><strong>CSS</strong> - Makes your website look beautiful</li><li><strong>JavaScript</strong> - Adds interactivity</li><li><strong>PHP</strong> - Server-side programming</li></ul><p>Start your journey today and build something amazing!</p>', 1],
            ['The Future of Artificial Intelligence', 'the-future-of-artificial-intelligence', '<p>Artificial Intelligence is transforming every industry. From healthcare to finance, AI is reshaping how we work and live.</p><h3>Current Trends</h3><p>Machine learning, natural language processing, and computer vision are driving innovation across sectors.</p>', 1],
            ['10 Tips for Healthy Living', '10-tips-for-healthy-living', '<p>Living a healthy lifestyle doesn\'t have to be complicated.</p><ol><li>Drink plenty of water</li><li>Get at least 7-8 hours of sleep</li><li>Exercise regularly</li><li>Eat a balanced diet</li><li>Practice mindfulness</li></ol>', 4]
        ];
        foreach ($samplePosts as [$title, $slug, $content, $catId]) {
            try {
                $pdo->prepare("INSERT INTO posts (title, slug, content, category_id, author_id, status) VALUES (?, ?, ?, ?, 1, 'published')")->execute([$title, $slug, $content, $catId]);
            } catch (PDOException $e) { /* already exists */ }
        }
        $results[] = "✅ Sample blog posts created";

        $results[] = "";
        $results[] = "🎉 Installation complete!";

    } catch (PDOException $e) {
        $errors[] = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-card { background: #1e293b; border-radius: 16px; padding: 40px; max-width: 600px; width: 100%; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .install-card h1 { font-size: 28px; margin-bottom: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .install-card .subtitle { color: #94a3b8; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px 16px; border: 2px solid #334155; border-radius: 8px; background: #0f172a; color: #e2e8f0; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-group input:focus { border-color: #6366f1; }
        .form-group small { color: #64748b; font-size: 12px; margin-top: 4px; display: block; }
        .btn { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4); }
        .results { margin-top: 24px; padding: 20px; background: #0f172a; border-radius: 8px; }
        .results p { padding: 4px 0; font-size: 14px; }
        .error { color: #f87171; }
        .success-box { margin-top: 20px; padding: 20px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px; }
        .success-box a { color: #6366f1; text-decoration: none; font-weight: 600; }
        .warning { margin-top: 16px; padding: 12px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.2); border-radius: 8px; font-size: 13px; color: #fbbf24; }
    </style>
</head>
<body>
    <div class="install-card">
        <h1>🚀 <?= APP_NAME ?> Installer</h1>
        <p class="subtitle">Setup your CMS database and create admin account</p>

        <?php if (empty($results) && empty($errors)): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Admin Name</label>
                    <input type="text" name="admin_name" value="Admin" required>
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" value="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label>Admin Password</label>
                    <input type="password" name="admin_password" value="admin123" required minlength="6">
                    <small>Minimum 6 characters. Change this from the default!</small>
                </div>
                <button type="submit" name="install" class="btn">⚡ Install Now</button>
            </form>
            <div class="warning">
                ⚠️ Make sure you've updated <code>config/config.php</code> with your database credentials before installing!
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="results">
                <?php foreach ($errors as $err): ?>
                    <p class="error"><?= $err ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <div class="results">
                <?php foreach ($results as $r): ?>
                    <p><?= $r ?></p>
                <?php endforeach; ?>
            </div>
            <div class="success-box">
                <p>🔗 <a href="<?= APP_URL ?>/">Visit Website</a></p>
                <p>🔗 <a href="<?= APP_URL ?>/admin/">Admin Panel</a></p>
                <p style="margin-top: 10px; font-size: 13px; color: #94a3b8;">
                    Admin: <?= h($_POST['admin_email'] ?? 'admin@example.com') ?> / <?= h($_POST['admin_password'] ?? 'admin123') ?>
                </p>
            </div>
            <div class="warning">
                🔴 DELETE this <code>install.php</code> file after installation for security!
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
