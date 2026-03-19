<?php
ob_start(); // Buffer any early warnings
require_once __DIR__ . '/includes/functions.php';
ob_end_clean(); // Clear accidental whitespace/output

// Set XML Header
header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// 1. Home Page
echo '  <url>' . PHP_EOL;
echo '    <loc>' . APP_URL . '/</loc>' . PHP_EOL;
echo '    <changefreq>daily</changefreq>' . PHP_EOL;
echo '    <priority>1.0</priority>' . PHP_EOL;
echo '  </url>' . PHP_EOL;

// 2. Blog Index
echo '  <url>' . PHP_EOL;
echo '    <loc>' . APP_URL . '/blog.php</loc>' . PHP_EOL;
echo '    <changefreq>daily</changefreq>' . PHP_EOL;
echo '    <priority>0.8</priority>' . PHP_EOL;
echo '  </url>' . PHP_EOL;

// 3. Published Pages (excluding admin pages)
$pages = db()->query("SELECT slug, updated_at, created_at FROM pages WHERE status = 'published' ORDER BY created_at DESC")->fetchAll();
foreach ($pages as $p) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . pageUrl($p['slug']) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . date('Y-m-d', strtotime($p['updated_at'] ?? $p['created_at'])) . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
    echo '    <priority>0.7</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 4. Published Posts
$posts = db()->query("
    SELECT p.id, p.slug, p.updated_at, p.created_at, c.slug as category_slug 
    FROM posts p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'published' 
    ORDER BY p.created_at DESC
")->fetchAll();

foreach ($posts as $post) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . postUrl($post['slug'], $post['category_slug'], $post['id']) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . date('Y-m-d', strtotime($post['updated_at'] ?? $post['created_at'])) . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
    echo '    <priority>0.6</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

echo '</urlset>' . PHP_EOL;
