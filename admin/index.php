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
$userPlan = getPlanByUserId($_currentUser['id']);

$update = checkForUpdates();
$migrationNeeded = isMigrationNeeded();
?>

<!-- Update & Migration Alerts -->
<div class="dashboard-alerts">
    <?php if ($update): ?>
        <div class="alert alert-info">
            <div class="alert-icon"><i class="fas fa-sync-alt fa-spin"></i></div>
            <div class="alert-body">
                <strong>Update available: Version <?= h($update['version']) ?></strong>
                <p><?= h($update['message'] ?? 'New features and security fixes are ready.') ?></p>
            </div>
            <a href="<?= APP_URL ?>/admin/update-core.php" class="btn btn-sm btn-primary">Update Now</a>
            <a href="https://github.com/Vivdeveloper/vivek" target="_blank" class="btn btn-sm btn-outline">Review Update</a>
        </div>
    <?php endif; ?>

    <?php if ($migrationNeeded): ?>
        <div class="alert alert-warning">
            <div class="alert-icon"><i class="fas fa-database"></i></div>
            <div class="alert-body">
                <strong>Database Sync Required</strong>
                <p>New columns are missing in your tables. Please run the migration to avoid 500 errors.</p>
            </div>
            <a href="<?= APP_URL ?>/run_migration.php" class="btn btn-sm btn-primary">Run Sync Now</a>
        </div>
    <?php endif; ?>
</div>

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

<?php if ($userPlan): ?>
<div class="mod-plan-banner animate-slide-up">
    <div class="banner-glass">
        <div class="banner-content">
            <div class="banner-meta">
                <span class="badge-premium"><i class="fas fa-gem"></i> My Active Plan</span>
                <h2><?= h($userPlan['name']) ?></h2>
                <p><?= truncate(h($userPlan['description']), 100) ?></p>
            </div>
            <div class="banner-right">
                <div class="price-display">₹<?= number_format($userPlan['price'], 0) ?></div>
                <a href="<?= APP_URL ?>/admin/my-plan.php" class="btn btn-plan-manage">Manage Plan</a>
            </div>
        </div>
        <div class="banner-circle"></div>
    </div>
</div>
<?php endif; ?>

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