<?php
/**
 * router.php — Clean Permalink Router
 *
 * Handles clean URLs like:
 *   /my-blog-post      → post.php
 *   /about             → page.php
 *   /contact           → page.php
 *
 * Called by .htaccess for any unmatched slug.
 */
require_once __DIR__ . '/includes/functions.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If using PHP's built-in server, return false for existing files (like css/js) or directories with index.php
if (php_sapi_name() === 'cli-server') {
    if (is_file(__DIR__ . $uri)) {
        return false;
    }
    if (is_dir(__DIR__ . $uri) && is_file(__DIR__ . $uri . '/index.php')) {
        return false;
    }
}

$slug = $_GET['slug'] ?? ltrim($uri, '/');
$catSlug = $_GET['cat'] ?? '';

// Handle Clean Category URLs: /category/some-slug
if (preg_match('/^category\/(.+)$/', $slug, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/category.php';
    exit;
}

// Handle Clean Tag URLs: /tag/some-slug
if (preg_match('/^tag\/(.+)$/', $slug, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/tag.php';
    exit;
}

// Handle sitemap.xml request
if ($slug === 'sitemap.xml' || $uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    exit;
}

// Handle robots.txt dynamic request
if ($slug === 'robots.txt' || $uri === '/robots.txt') {
    header("Content-Type: text/plain");
    require __DIR__ . '/robots.php';
    exit;
}

// Serve index.php for root path
if (!$slug && !$catSlug && ($uri === '/' || $uri === '')) {
    require __DIR__ . '/index.php';
    exit;
}

// Case 1: /category/post-slug or /parent-slug/child-slug
// Case 2: /slug

// 1. Try to find a blog post with this slug
$statusFilter = canEdit() ? "" : " AND p.status = 'published'";
$postQuery = "SELECT p.id, p.slug, c.slug as cat_slug 
              FROM posts p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.slug = ? " . $statusFilter;
$postStmt = db()->prepare($postQuery);
$postStmt->execute([$slug]);
$foundPost = $postStmt->fetch();

if ($foundPost) {
    $structure = getSetting('permalink_structure', 'post_name');

    // Validate requested URL against structure setting
    if ($structure === 'category_post_name') {
        // If we are in category mode, the first part of the URL (cat) must match category slug
        if ($catSlug === $foundPost['cat_slug']) {
            $_GET['slug'] = $slug;
            require __DIR__ . '/post.php';
            exit;
        }
    }
    else {
        // Simple slug mode: cat should be empty OR we are forgiving
        if (!$catSlug) {
            $_GET['slug'] = $slug;
            require __DIR__ . '/post.php';
            exit;
        }
    }
}

// 2. Try to find a CMS page with this slug
// Only check if it's a one-level URL (/about) as our pages aren't hierarchical yet
if (!$catSlug) {
    $foundPage = db()->prepare("SELECT id FROM pages WHERE slug = ? " . ($statusFilter ? " AND status = 'published'" : ""));
    $foundPage->execute([$slug]);
    if ($foundPage->fetch()) {
        $_GET['slug'] = $slug;
        require __DIR__ . '/page.php';
        exit;
    }
}

// 3. Nothing found → 404
http_response_code(404);
$pageTitle = '404 — Page Not Found';
require_once __DIR__ . '/includes/header.php';
echo '<section class="section"><div class="container"><div class="error-page">
    <div class="error-icon"><i class="fas fa-ghost"></i></div>
    <h1>404</h1>
    <p>This page seems to have wandered off.</p>
    <a href="' . APP_URL . '/" class="btn btn-primary btn-lg"><i class="fas fa-home"></i> Go Home</a>
</div></div></section>';
require_once __DIR__ . '/includes/footer.php';
exit;