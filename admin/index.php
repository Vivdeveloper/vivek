<?php
/**
 * WordPress Style Dashboard
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$totalUsers = countUsers();
$totalPosts = countPosts();
$totalComments = countComments();
$recentPosts = getRecentPosts(5);
?>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-card-label">At a Glance</span>
        <div class="stat-card-value"><?= $totalPosts ?> Posts</div>
        <div class="stat-card-value"><?= $totalComments ?> Comments</div>
    </div>
    <div class="stat-card">
        <span class="stat-card-label">Users</span>
        <div class="stat-card-value"><?= $totalUsers ?> Registered</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="modern-card">
        <div class="card-header">Activity</div>
        <div class="card-body">
            <h4 style="font-size:13px; font-weight:600; margin-bottom:10px;">Recently Published</h4>
            <ul style="list-style:none; font-size:13px; color:var(--text-secondary);">
                <?php if (!empty($recentPosts)): foreach ($recentPosts as $post): ?>
                    <li style="margin-bottom:8px; display:flex; justify-content:space-between;">
                        <span><?= formatDate($post['created_at']) ?></span>
                        <a href="<?= APP_URL ?>/admin/post-edit.php?id=<?= $post['id'] ?>" style="color:#2271b1; text-decoration:none;"><?= h($post['title']) ?></a>
                    </li>
                <?php endforeach; else: ?>
                    <li>No recent activity.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="modern-card">
        <div class="card-header">Quick Draft</div>
        <div class="card-body">
             <form action="post-create.php" method="GET">
                <div class="form-group">
                    <input type="text" name="title" placeholder="Title" required>
                </div>
                <div class="form-group">
                    <textarea placeholder="What's on your mind?" style="height:100px;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Draft</button>
             </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>