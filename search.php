<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Search';
$query = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$posts = []; $pagination = ['total_pages' => 0, 'current_page' => 1];
if ($query) {
    $pageTitle = 'Search: ' . $query;
    $total = countSearchPosts($query);
    $pagination = paginate($total, $page);
    $posts = searchPosts($query, $pagination['per_page'], $pagination['offset']);
}
$categories = getCategoriesWithCount();
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header"><div class="container"><h1>Search Results</h1><p>Results for "<?= h($query) ?>"</p></div></section>
<section class="section"><div class="container"><div class="blog-layout"><div class="blog-main">
<form action="<?= APP_URL ?>/search.php" method="GET" class="search-form search-form-large">
<input type="text" name="q" value="<?= h($query) ?>" placeholder="Search articles..." required>
<button type="submit"><i class="fas fa-search"></i> Search</button></form>
<?php if (!empty($posts)): ?>
<p class="search-results-count"><?= count($posts) ?> result(s) found</p>
<div class="posts-grid posts-grid-compact">
<?php foreach ($posts as $post): ?>
<article class="post-card"><div class="post-card-image">
<?php if ($post['featured_image']): ?><img src="<?= APP_URL.'/'.h($post['featured_image']) ?>" alt="<?= h($post['title']) ?>" loading="lazy">
<?php else: ?><div class="post-card-placeholder"><i class="fas fa-image"></i></div><?php endif; ?>
<?php if ($post['category_name']): ?><span class="post-card-category"><?= h($post['category_name']) ?></span><?php endif; ?>
</div><div class="post-card-body"><h3 class="post-card-title"><a href="<?= postUrl($post['slug'], $post['category_slug'], $post['id']) ?>"><?= h($post['title']) ?></a></h3>
<p class="post-card-excerpt"><?= truncate($post['content'], 120) ?></p>
<div class="post-card-meta"><span><i class="fas fa-user"></i> <?= h($post['author_name']) ?></span><span><i class="fas fa-calendar"></i> <?= formatDate($post['created_at']) ?></span></div>
</div></article>
<?php endforeach; ?>
</div>
<?php if ($pagination['total_pages'] > 1): ?><div class="pagination">
<?php if ($pagination['current_page'] > 1): ?><a href="?q=<?= urlencode($query) ?>&page=<?= $pagination['current_page']-1 ?>" class="pagination-btn">&laquo; Prev</a><?php endif; ?>
<?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?><a href="?q=<?= urlencode($query) ?>&page=<?= $i ?>" class="pagination-btn <?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a><?php endfor; ?>
<?php if ($pagination['current_page'] < $pagination['total_pages']): ?><a href="?q=<?= urlencode($query) ?>&page=<?= $pagination['current_page']+1 ?>" class="pagination-btn">Next &raquo;</a><?php endif; ?>
</div><?php endif; ?>
<?php elseif ($query): ?>
<div class="empty-state"><i class="fas fa-search"></i><h3>No results found</h3><p>Try different keywords.</p><a href="<?= APP_URL ?>/blog.php" class="btn btn-primary">Browse All Posts</a></div>
<?php endif; ?>
</div>
<aside class="blog-sidebar">
<?php if (!empty($categories)): ?><div class="sidebar-widget"><h3 class="widget-title">Categories</h3><ul class="widget-list">
<?php foreach ($categories as $cat): ?><li><a href="<?= categoryUrl($cat['slug']) ?>"><span><?= h($cat['name']) ?></span><span class="badge"><?= $cat['post_count'] ?></span></a></li><?php endforeach; ?>
</ul></div><?php endif; ?>
</aside></div></div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
