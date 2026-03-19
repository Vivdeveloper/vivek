<?php
require_once __DIR__ . '/includes/functions.php';
$slug = $_GET['slug'] ?? '';
if (!$slug) { redirect(APP_URL . '/blog.php'); }
$category = getCategoryBySlug($slug);
if (!$category) {
    $pageTitle = '404'; require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="error-page"><div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div><h1>404</h1><p>Category not found</p><a href="'.APP_URL.'/blog.php" class="btn btn-primary">Back to Blog</a></div></div></section>';
    require_once __DIR__ . '/includes/footer.php'; exit;
}
$pageTitle = 'Category: ' . $category['name'];
$page = max(1, intval($_GET['page'] ?? 1));
$totalPosts = countPostsByCategory($slug);
$pagination = paginate($totalPosts, $page);
$posts = getPostsByCategory($slug, $pagination['per_page'], $pagination['offset']);
$categories = getCategoriesWithCount();
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header"><div class="container"><h1>Category: <?= h($category['name']) ?></h1><p>All posts in <?= h($category['name']) ?></p></div></section>
<section class="section"><div class="container"><div class="blog-layout"><div class="blog-main">
<?php if (!empty($posts)): ?>
<div class="posts-grid posts-grid-compact">
<?php foreach ($posts as $post): ?>
<article class="post-card"><div class="post-card-image">
<?php if ($post['featured_image']): ?><img src="<?= APP_URL.'/'.h($post['featured_image']) ?>" alt="<?= h($post['title']) ?>" loading="lazy">
<?php else: ?><div class="post-card-placeholder"><i class="fas fa-image"></i></div><?php endif; ?>
</div><div class="post-card-body"><h3 class="post-card-title"><a href="<?= postUrl($post['slug'], $post['category_slug'], $post['id']) ?>"><?= h($post['title']) ?></a></h3>
<p class="post-card-excerpt"><?= truncate($post['content'], 120) ?></p>
<div class="post-card-meta"><span><i class="fas fa-user"></i> <?= h($post['author_name']) ?></span><span><i class="fas fa-calendar"></i> <?= formatDate($post['created_at']) ?></span></div>
</div></article>
<?php endforeach; ?>
</div>
<?php if ($pagination['total_pages'] > 1): ?><div class="pagination">
<?php if ($pagination['current_page'] > 1): ?><a href="?slug=<?= h($slug) ?>&page=<?= $pagination['current_page']-1 ?>" class="pagination-btn">&laquo; Prev</a><?php endif; ?>
<?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?><a href="?slug=<?= h($slug) ?>&page=<?= $i ?>" class="pagination-btn <?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a><?php endfor; ?>
<?php if ($pagination['current_page'] < $pagination['total_pages']): ?><a href="?slug=<?= h($slug) ?>&page=<?= $pagination['current_page']+1 ?>" class="pagination-btn">Next &raquo;</a><?php endif; ?>
</div><?php endif; ?>
<?php else: ?>
<div class="empty-state"><i class="fas fa-folder-open"></i><h3>No posts in this category</h3><p>Check back later or browse other categories.</p><a href="<?= APP_URL ?>/blog.php" class="btn btn-primary">Browse All Posts</a></div>
<?php endif; ?>
</div>
<aside class="blog-sidebar">
<div class="sidebar-widget"><h3 class="widget-title">Search</h3><form action="<?= APP_URL ?>/search.php" method="GET" class="search-form"><input type="text" name="q" placeholder="Search..." required><button type="submit"><i class="fas fa-search"></i></button></form></div>
<?php if (!empty($categories)): ?><div class="sidebar-widget"><h3 class="widget-title">Categories</h3><ul class="widget-list">
<?php foreach ($categories as $cat): ?><li class="<?= $cat['slug'] === $slug ? 'active' : '' ?>"><a href="<?= categoryUrl($cat['slug']) ?>"><span><?= h($cat['name']) ?></span><span class="badge"><?= $cat['post_count'] ?></span></a></li><?php endforeach; ?>
</ul></div><?php endif; ?>
</aside></div></div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
