<?php
/**
 * Single Post Page — Premium Design
 */
require_once __DIR__ . '/includes/functions.php';
startSecureSession();

// Setup Math Captcha
if (!isset($_SESSION['math_num1'])) {
    $_SESSION['math_num1'] = rand(1, 9);
    $_SESSION['math_num2'] = rand(1, 9);
}
$num1 = $_SESSION['math_num1'];
$num2 = $_SESSION['math_num2'];

$slug = $_GET['slug'] ?? '';
$id = intval($_GET['p'] ?? 0);

if ($id) {
    $post = getPostById($id);
    // Extra security: if not admin, must be published
    if ($post && $post['status'] !== 'published' && !canEdit()) {
        $post = null;
    }
} elseif ($slug) {
    $post = getPostBySlug($slug);
} else {
    redirect(APP_URL . '/blog.php');
}

if (!$post) {
    $pageTitle = '404 - Post Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="error-page"><div class="error-icon"><i class="fas fa-ghost"></i></div><h1>404</h1><p>We couldn\'t find this post. It may have been removed.</p><a href="' . APP_URL . '/blog.php" class="btn btn-primary btn-lg"><i class="fas fa-arrow-left"></i> Back to Blog</a></div></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $post['title'];
$relatedPosts = getRecentPosts(4);
$categories = getCategoriesWithCount();
$comments = getCommentsByPost($post['id']);
$commentCount = count($comments);
$postTags = getPostTags($post['id']);
$allTags = getTagsWithCount();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $comment = trim($_POST['comment'] ?? '');
    $authorName = trim($_POST['author_name'] ?? '');
    $authorEmail = trim($_POST['author_email'] ?? '');
    $userId = $_SESSION['user_id'] ?? null;

    $userAnswer = intval($_POST['math_answer'] ?? 0);
    $realAnswer = $_SESSION['math_num1'] + $_SESSION['math_num2'];

    // Refresh for next time
    $_SESSION['math_num1'] = rand(1, 9);
    $_SESSION['math_num2'] = rand(1, 9);

    if ($userAnswer !== $realAnswer) {
        setFlash('error', 'Wrong math answer. Please try again!');
    } elseif ($comment) {
        $stmt = db()->prepare("INSERT INTO comments (post_id, user_id, author_name, author_email, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$post['id'], $userId, $authorName ?: null, $authorEmail ?: null, $comment]);
        setFlash('success', 'Your comment has been submitted for review!');
        redirect(postUrl($slug));
    }
}

require_once __DIR__ . '/includes/header.php';

$shareUrl = urlencode(postUrl($slug));
$shareTitle = urlencode($post['title']);

// Custom CSS Support
if (!empty($post['custom_css'])) {
    $GLOBALS['extra_css'] = ($GLOBALS['extra_css'] ?? '') . "\n<style>" . $post['custom_css'] . "</style>";
}
?>

<?php if ($post['status'] !== 'published'): ?>
    <div style="background: #e37400; color: white; padding: 10px; text-align: center; position: sticky; top: 0; z-index: 1000; font-weight: 500; font-family: sans-serif;">
        <i class="fas fa-eye" style="margin-right: 8px;"></i> <strong>PREVIEW MODE</strong>: This post is a <strong><?= strtoupper($post['status']) ?></strong> and not visible to the public.
    </div>
<?php endif; ?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="page-header-breadcrumb">
            <a href="<?= APP_URL ?>/">Home</a> <span>/</span>
            <a href="<?= APP_URL ?>/blog.php">Blog</a> <span>/</span>
            <span><?= h(substr($post['title'], 0, 40)) ?>...</span>
        </div>
        <h1><?= h($post['title']) ?></h1>
        <p>
            <i class="fas fa-user"></i> <?= h($post['author_name']) ?> &nbsp;·&nbsp;
            <i class="fas fa-calendar-alt"></i> <?= formatDate($post['created_at']) ?> &nbsp;·&nbsp;
            <i class="fas fa-comments"></i> <?= $commentCount ?> Comments
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="blog-layout">
            <!-- Main Post Content -->
            <article>
                <?php if ($post['category_name']): ?>
                    <a href="<?= APP_URL ?>/category.php?slug=<?= h($post['category_slug']) ?>" class="post-category-badge">
                        <i class="fas fa-tag"></i> <?= h($post['category_name']) ?>
                    </a>
                <?php endif; ?>

                <?php if ($post['featured_image']): ?>
                    <div class="post-detail-image">
                        <img src="<?= APP_URL . '/' . h($post['featured_image']) ?>" alt="<?= h($post['title']) ?>">
                    </div>
                <?php endif; ?>

                <div class="post-detail-content">
                    <?= $post['content'] ?>
                </div>

                <!-- Post Tags -->
                <?php if (!empty($postTags)): ?>
                    <div class="post-tags-list">
                        <i class="fas fa-tags" style="color: #64748b; margin-right: 8px;"></i>
                        <?php foreach ($postTags as $tag): ?>
                            <a href="<?= tagUrl($tag['slug']) ?>" class="post-tag-item">#<?= h($tag['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Share -->
                <div class="post-share">
                    <span>Share this article:</span>
                    <div class="share-btns">
                        <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" class="share-btn share-twitter" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" class="share-btn share-facebook" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://linkedin.com/shareArticle?mini=true&url=<?= $shareUrl ?>&title=<?= $shareTitle ?>" target="_blank" class="share-btn share-linkedin" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" class="share-btn share-whatsapp" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Comments -->
                <?php if ($post['allow_comments']): ?>
                <div class="comments-section">
                    <h3 class="comments-title"><i class="fas fa-comments"></i> Comments (<?= $commentCount ?>)</h3>
                    
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="comment-item">
                                <div class="comment-avatar"><i class="fas fa-user-circle"></i></div>
                                <div class="comment-body">
                                    <div class="comment-header">
                                        <strong><?= h($c['user_name'] ?? $c['author_name'] ?? 'Anonymous') ?></strong>
                                        <span class="comment-date"><?= formatDate($c['created_at']) ?></span>
                                    </div>
                                    <p><?= h($c['comment']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-comments">Be the first to share your thoughts!</p>
                    <?php endif; ?>

                    <!-- Comment Form -->
                    <div class="comment-form-wrapper">
                        <h4><i class="fas fa-pen" style="color: var(--accent-1); margin-right: 8px;"></i>Leave a Comment</h4>
                        <form action="" method="POST">
                            <?php csrfField(); ?>
                            <?php if (!isLoggedIn()): ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="author_name">Name *</label>
                                    <input type="text" id="author_name" name="author_name" placeholder="Your name" required>
                                </div>
                                <div class="form-group">
                                    <label for="author_email">Email</label>
                                    <input type="email" id="author_email" name="author_email" placeholder="Your email (optional)">
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="comment">Your Comment *</label>
                                <textarea id="comment" name="comment" rows="4" placeholder="Share your thoughts..." required></textarea>
                            </div>
                            <div class="form-group" style="margin-top: 10px; background: #f8faff; padding: 10px; border-radius: 6px; border: 1px solid #dce4f5; display: inline-flex; align-items: center; gap: 10px;">
                                <label for="math_answer" style="margin: 0; font-weight: 600; font-size: 14px; color: #3b5998;">Security: What is <?= $num1 ?> + <?= $num2 ?>? *</label>
                                <input type="number" id="math_answer" name="math_answer" required style="width: 70px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-paper-plane"></i> Post Comment
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </article>

            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <!-- Search -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Search</h3>
                    <form action="<?= APP_URL ?>/search.php" method="GET" class="search-form">
                        <input type="text" name="q" placeholder="Search posts..." required>
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <!-- Categories -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Categories</h3>
                    <ul class="widget-list">
                        <?php foreach ($categories as $cat): ?>
                            <li><a href="<?= categoryUrl($cat['slug']) ?>"><?= h($cat['name']) ?> <span class="badge"><?= $cat['post_count'] ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Related -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Recent Posts</h3>
                    <ul class="widget-posts">
                        <?php foreach ($relatedPosts as $rp): ?>
                            <li>
                                <a href="<?= postUrl($rp['slug'], $rp['category_slug'], $rp['id']) ?>">
                                    <h4><?= h($rp['title']) ?></h4>
                                    <span><i class="fas fa-calendar-alt"></i> <?= formatDate($rp['created_at']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Tags Widget -->
                <?php if (!empty($allTags)): ?>
                <div class="sidebar-widget">
                    <h3 class="widget-title">Tag Cloud</h3>
                    <div class="tag-cloud">
                        <?php foreach ($allTags as $tag): ?>
                            <a href="<?= tagUrl($tag['slug']) ?>" class="tag-cloud-item">
                                <?= h($tag['name']) ?> <small>(<?= $tag['post_count'] ?>) </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
