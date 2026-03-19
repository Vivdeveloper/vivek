<?php
/**
 * Blog Listing Page — Premium Design
 */
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Blog';
$page = max(1, intval($_GET['page'] ?? 1));
$totalPosts = countPosts('published');
$pagination = paginate($totalPosts, $page);
$posts = getPosts($pagination['per_page'], $pagination['offset']);
$categories = getCategoriesWithCount();
$recentPosts = getRecentPosts(5);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="page-header-breadcrumb">
            <a href="<?= APP_URL ?>/">Home</a> <span>/</span> <span>Blog</span>
        </div>
        <h1>Our <span class="gradient-text">Blog</span></h1>
        <p>Stories, insights, and ideas — fresh from the desk</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="blog-layout">
            <!-- Posts -->
            <div>
                <?php if (!empty($posts)): ?>
                    <div class="posts-grid posts-grid-compact">
                        <?php foreach ($posts as $post): ?>
                            <article class="post-card">
                                <div class="post-card-image">
                                    <?php if ($post['featured_image']): ?>
                                        <img src="<?= APP_URL . '/' . h($post['featured_image']) ?>" alt="<?= h($post['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="post-card-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                    <?php if ($post['category_name']): ?>
                                        <span class="post-card-category"><?= h($post['category_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="post-card-body">
                                    <h3 class="post-card-title">
                                        <a href="<?= postUrl($post['slug'], $post['category_slug'], $post['id']) ?>"><?= h($post['title']) ?></a>
                                    </h3>
                                    <p class="post-card-excerpt"><?= truncate($post['content'], 100) ?></p>
                                    <div class="post-card-meta">
                                        <span><i class="fas fa-user"></i> <?= h($post['author_name']) ?></span>
                                        <span><i class="fas fa-calendar"></i> <?= formatDate($post['created_at']) ?></span>
                                        <?php if ($post['allow_comments']): ?>
                                        <span><a href="<?= postUrl($post['slug'], $post['category_slug'], $post['id']) ?>#comments" style="color: inherit;"><i class="fas fa-comments"></i> Connect</a></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="pagination-btn"><i class="fas fa-chevron-left"></i> Prev</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <a href="?page=<?= $i ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $pagination['total_pages']): ?>
                                <a href="?page=<?= $page + 1 ?>" class="pagination-btn">Next <i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-pen-nib"></i>
                        <h3>No posts yet</h3>
                        <p>Check back soon for fresh content!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <div class="sidebar-widget">
                    <h3 class="widget-title">Search</h3>
                    <form action="<?= APP_URL ?>/search.php" method="GET" class="search-form">
                        <input type="text" name="q" placeholder="Search posts..." required>
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <div class="sidebar-widget">
                    <h3 class="widget-title">Categories</h3>
                    <ul class="widget-list">
                        <?php foreach ($categories as $cat): ?>
                            <li><a href="<?= categoryUrl($cat['slug']) ?>"><?= h($cat['name']) ?> <span class="badge"><?= $cat['post_count'] ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-widget">
                    <h3 class="widget-title">Recent Posts</h3>
                    <ul class="widget-posts">
                        <?php foreach ($recentPosts as $rp): ?>
                            <li>
                                <a href="<?= postUrl($rp['slug'], $rp['category_slug'], $rp['id']) ?>">
                                    <h4><?= h($rp['title']) ?></h4>
                                    <span><i class="fas fa-calendar-alt"></i> <?= formatDate($rp['created_at']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
