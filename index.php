<?php
/**
 * Home Page — SEO Website Designer Agency
 */
require_once __DIR__ . '/includes/functions.php';

// WordPress Fallback Style: Check for SEO files if they reach index.php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    exit;
}
if ($uri === '/robots.txt') {
    header("Content-Type: text/plain");
    require __DIR__ . '/robots.php';
    exit;
}

// Fallback: If we are not at the home root, send to router.php to handle permalinks
if ($uri !== '/' && $uri !== '/index.php' && $uri !== '') {
    // If the file actually exists on disk, let the server handle it (CSS, JS, images)
    if (!is_file(__DIR__ . $uri)) {
        require __DIR__ . '/router.php';
        exit;
    }
}

$pageTitle = 'Home';

// CMS-driven homepage (no hardcoded design).
$frontPageId = getSetting('front_page_id');
if ($frontPageId) {
    $page = getPageById($frontPageId);
} else {
    $page = getPageBySlug('home');
}

if (empty($page)) {
    // Fallback or auto-create...
    $page = getPageBySlug('home');
    if (empty($page)) {
        try {
            $stmt = db()->prepare("
                INSERT INTO pages (title, slug, content, featured_image, custom_css, status, template)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(['Home', 'home', '', null, '', 'published', 'default']);
            $page = getPageBySlug('home');
        } catch (Throwable $e) {
            $page = null;
        }
    }
}

if (empty($page)) {
    $page = ['template' => 'default', 'custom_css' => '', 'content' => '', 'title' => 'Home', 'featured_image' => null];
    $pageTitle = 'Home';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="error-page"><h1>Home not found in CMS</h1><p>Go to Admin → Theme Settings → Permalinks to set your Front Page, or create a page with slug <code>home</code>.</p></div></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $page['title'] ?? 'Home';

// Inline <style> support: extract from page content and inject into page custom_css (<head>).
$inlineCss = '';
if (!empty($page['content'])) {
    if (preg_match_all('#<style\\b[^>]*>(.*?)</style>#is', $page['content'], $matches)) {
        $inlineCss = implode("\n", array_map('trim', $matches[1]));
        $page['content'] = preg_replace('#<style\\b[^>]*>.*?</style>#is', '', $page['content']);
    }
}
if (!empty($inlineCss)) {
    $page['custom_css'] = ($page['custom_css'] ?? '') . "\n" . $inlineCss;
}

require_once __DIR__ . '/includes/header.php';
?>

<?php
$template = $page['template'] ?? 'default';
if ($template === 'canvas'): ?>
    <div class="page-canvas">
        <?= $page['content'] ?>
    </div>
<?php else: ?>
    <?php if ($template === 'default'): ?>
    <section class="page-header <?= !empty($page['featured_image']) ? 'page-header-with-img' : '' ?>">
        <?php if (!empty($page['featured_image'])): ?>
            <div class="page-header-bg"><img src="<?= APP_URL . '/' . h($page['featured_image']) ?>" alt="<?= h($page['title']) ?>"></div>
        <?php endif; ?>
        <div class="container" style="position: relative; z-index: 2;">
            <div class="page-header-breadcrumb">
                <a href="<?= APP_URL ?>/">Home</a> <span>/</span> <span><?= h($page['title']) ?></span>
            </div>
            <h1><?= h($page['title']) ?></h1>
        </div>
    </section>
    <?php endif; ?>

    <section class="section <?= $template === 'full-width' ? 'page-full-width' : '' ?>">
        <div class="<?= $template === 'full-width' ? '' : 'container' ?>">
            <div class="page-content <?= $template === 'full-width' ? 'page-full-width' : '' ?>">
                <?= $page['content'] ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
