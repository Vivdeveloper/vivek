<?php
/**
 * Dynamic CMS Pages — SEO Website Designer
 */
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$id = intval($_GET['id'] ?? 0);

if ($id) {
    $page = getPageById($id);
    if ($page && $page['status'] !== 'published' && !canEdit()) {
        $page = null;
    }
} elseif ($slug) {
    $page = getPageBySlug($slug);
} else {
    redirect(APP_URL . '/');
}

if (!$page) {
    $pageTitle = '404';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="error-page"><div class="error-icon"><i class="fas fa-ghost"></i></div><h1>404</h1><p>This page seems to have wandered off...</p><a href="' . APP_URL . '/" class="btn btn-primary btn-lg"><i class="fas fa-home"></i> Go Home</a></div></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $page['title'];

// Inline <style> support inside Page HTML:
// If admin pastes "<style>...</style>" into pages.content, we extract it and
// inject into the page header (<head>) for correct preview.
$inlineCss = '';
if (!empty($page['content'])) {
    if (preg_match_all('#<style\\b[^>]*>(.*?)</style>#is', $page['content'], $matches)) {
        $inlineCss = implode("\n", array_map('trim', $matches[1]));
        // Remove style blocks from body content after extracting.
        $page['content'] = preg_replace('#<style\\b[^>]*>.*?</style>#is', '', $page['content']);
    }
}

if (!empty($inlineCss)) {
    $page['custom_css'] = ($page['custom_css'] ?? '') . "\n" . $inlineCss;
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($page['status'] !== 'published'): ?>
    <div style="background: #e37400; color: white; padding: 10px; text-align: center; position: sticky; top: 0; z-index: 1000; font-weight: 500; font-family: sans-serif;">
        <i class="fas fa-eye" style="margin-right: 8px;"></i> <strong>PREVIEW MODE</strong>: This page is a <strong><?= strtoupper($page['status']) ?></strong> and not visible to the public.
    </div>
<?php endif; ?>

<?php
// Template setting
$template = $page['template'] ?? 'default';

if ($template === 'canvas'): ?>
    <!-- Canvas Template (Blank CMS) -->
    <div class="page-canvas">
        <?= $page['content'] ?>
    </div>
<?php else: ?>
    <?php if ($template === 'default'): ?>
    <section class="page-header <?= !empty($page['featured_image']) ? 'page-header-with-img' : '' ?>">
        <?php if (!empty($page['featured_image'])): ?>
        <div class="page-header-bg"><img src="<?= APP_URL.'/'.h($page['featured_image']) ?>" alt="<?= h($page['title']) ?>"></div>
        <?php endif; ?>
        <div class="container" style="position: relative; z-index: 2;">
            <div class="page-header-breadcrumb">
                <a href="<?= APP_URL ?>/">Home</a> <span>/</span> <span><?= h($page['title']) ?></span>
            </div>
            <h1><?= h($page['title']) ?></h1>
        </div>
    </section>
    <?php endif; ?>

    <!-- WordPress-like: render ONLY from CMS content -->
    <section class="section <?= $template === 'full-width' ? 'page-full-width' : '' ?>">
        <div class="<?= $template === 'full-width' ? '' : 'container' ?>">
            <div class="page-content <?= $template === 'full-width' ? 'page-full-width' : '' ?>">
                <?= $page['content'] ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
