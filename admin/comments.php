<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

// Handle Comment Actions (Before any output to avoid headers already sent)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (!empty($_POST['single_approve'])) {
        $id = intval($_POST['single_approve']);
        db()->prepare("UPDATE comments SET status='approved' WHERE id=?")->execute([$id]);
        setFlash('success', 'Comment approved.');
    } elseif (!empty($_POST['single_spam'])) {
        $id = intval($_POST['single_spam']);
        db()->prepare("UPDATE comments SET status='spam' WHERE id=?")->execute([$id]);
        setFlash('success', 'Marked as spam.');
    } elseif (!empty($_POST['single_delete'])) {
        $id = intval($_POST['single_delete']);
        db()->prepare("DELETE FROM comments WHERE id=?")->execute([$id]);
        setFlash('success', 'Comment deleted.');
    } else {
        $action = $_POST['action'] ?? '';
        $bulkIds = $_POST['ids'] ?? [];
        
        if ($bulkIds && $action) {
            $validIds = array_map('intval', $bulkIds);
            if ($validIds) {
                $placeholders = implode(',', array_fill(0, count($validIds), '?'));
                if ($action === 'bulk_approve') {
                    db()->prepare("UPDATE comments SET status='approved' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' comments approved.');
                } elseif ($action === 'bulk_spam') {
                    db()->prepare("UPDATE comments SET status='spam' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' comments marked as spam.');
                } elseif ($action === 'bulk_delete') {
                    db()->prepare("DELETE FROM comments WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' comments deleted.');
                }
            }
        }
    }
    
    redirect(APP_URL . '/admin/comments.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
}

$pageTitle = 'Manage Comments';
require_once __DIR__ . '/includes/header.php';

$statusFilter = $_GET['status'] ?? null;
$comments = getAllComments(50, $statusFilter);
?>
<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Comments</h2>
            <p class="text-muted">Manage user comments and feedback</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs-container">
        <div class="filter-tabs">
            <a href="<?= APP_URL ?>/admin/comments.php" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
            <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
            <a href="?status=spam" class="filter-tab <?= $statusFilter === 'spam' ? 'active' : '' ?>">Spam</a>
        </div>
    </div>

    <form action="" method="POST" id="bulk-form">
        <?php csrfField(); ?>
        <div class="bulk-actions" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
            <select name="action" class="form-control" style="width: 200px;">
                <option value="">Bulk Actions</option>
                <option value="bulk_approve">Approve</option>
                <option value="bulk_spam">Mark as Spam</option>
                <option value="bulk_delete">Delete Permanently</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
        </div>

        <div class="modern-card no-padding overflow-hidden">
            <table class="modern-table">
                <thead><tr>
                    <th style="width: 40px;"><input type="checkbox" id="check-all"></th>
                    <th>Author</th><th>Comment</th><th>Post</th><th>Status</th><th>Date</th><th width="120">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (!empty($comments)): foreach ($comments as $c): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $c['id'] ?>" class="item-check"></td>
                    <td>
                        <div class="title-cell">
                            <strong><?= h($c['user_name'] ?? $c['author_name'] ?? 'Anonymous') ?></strong>
                            <?php if ($c['author_email']): ?><small><?= h($c['author_email']) ?></small><?php endif; ?>
                        </div>
                    </td>
                    <td class="comment-cell"><?= h(substr($c['comment'], 0, 100)) ?><?= strlen($c['comment']) > 100 ? '...' : '' ?></td>
                    <td><?php if ($c['post_title']): ?><a href="<?= postUrl($c['post_slug'], $c['post_category_slug'], $c['post_id']) ?>" target="_blank" style="color:var(--text-secondary);"><?= h(substr($c['post_title'], 0, 30)) ?></a><?php else: ?><span class="text-muted">Deleted</span><?php endif; ?></td>
                    <td><span class="status-badge status-<?= $c['status'] ?>"><?= $c['status'] ?></span></td>
                    <td><small><?= formatDate($c['created_at']) ?></small></td>
                    <td>
                        <div class="row-actions">
                            <?php if ($c['status'] !== 'approved'): ?>
                                <button type="submit" name="single_approve" value="<?= $c['id'] ?>" class="action-btn approve" title="Approve"><i class="fas fa-check"></i></button>
                            <?php endif; ?>
                            <?php if ($c['status'] !== 'spam'): ?>
                                <button type="submit" name="single_spam" value="<?= $c['id'] ?>" class="action-btn spam" title="Spam"><i class="fas fa-ban"></i></button>
                            <?php endif; ?>
                            <button type="submit" name="single_delete" value="<?= $c['id'] ?>" class="action-btn delete" title="Delete Permanently" onclick="return confirm('Delete this comment permanently?')"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-row-state">
                            <i class="fas fa-comments"></i>
                            <p>No comments found.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
document.getElementById('check-all').addEventListener('change', function() {
    const checks = document.querySelectorAll('.comment-check');
    checks.forEach(c => c.checked = this.checked);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
