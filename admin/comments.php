<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

// Handle Comment Actions (Pre-output)
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
        $bulkIds = (array)($_POST['ids'] ?? []);
        if ($bulkIds && $action) {
            $validIds = array_map('intval', $bulkIds);
            $phs = implode(',', array_fill(0, count($validIds), '?'));
            if ($action === 'bulk_approve') { db()->prepare("UPDATE comments SET status='approved' WHERE id IN ($phs)")->execute($validIds); }
            elseif ($action === 'bulk_spam') { db()->prepare("UPDATE comments SET status='spam' WHERE id IN ($phs)")->execute($validIds); }
            elseif ($action === 'bulk_delete') { db()->prepare("DELETE FROM comments WHERE id IN ($phs)")->execute($validIds); }
            setFlash('success', 'Bulk operation complete.');
        }
    }
    redirect(APP_URL . '/admin/comments.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
}

$statusFilter = $_GET['status'] ?? 'all';
$comments = getAllComments(100, $statusFilter === 'all' ? null : $statusFilter);

// Get counts for tabs
$counts = [
    'all' => db()->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'pending' => db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'approved' => db()->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn(),
    'spam' => db()->query("SELECT COUNT(*) FROM comments WHERE status = 'spam'")->fetchColumn()
];

$pageTitle = 'Comments Framework';
require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="max-width: 1400px; margin: 0 auto; padding-top: 20px;">
    
    <div class="admin-page-header" style="margin-bottom: 30px;">
        <div class="header-left">
            <h2 style="font-size: 24px; font-weight: 400; color: #1d2327;">Comments</h2>
            <p class="text-muted" style="font-size: 13px; margin-top: 5px;">Manage user discussions and moderation queue.</p>
        </div>
    </div>

    <!-- FILTER TABS (POST STYLE) -->
    <div class="filter-tabs-container" style="margin-bottom: 25px;">
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge"><?= $counts['all'] ?></span></a>
            <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending <span class="badge"><?= $counts['pending'] ?></span></a>
            <a href="?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved <span class="badge"><?= $counts['approved'] ?></span></a>
            <a href="?status=spam" class="filter-tab <?= $statusFilter === 'spam' ? 'active' : '' ?>">Spam <span class="badge"><?= $counts['spam'] ?></span></a>
        </div>
    </div>

    <!-- MASTER FORM & LIST VIEW -->
    <form action="" method="POST" id="bulk-form">
        <?php csrfField(); ?>
        <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
            <select name="action" class="form-control" style="width: 200px; height: 32px; font-size: 12px; border-radius: 4px; border: 1px solid #c3c4c7;">
                <option value="">Bulk Actions</option>
                <option value="bulk_approve">Approve</option>
                <option value="bulk_spam">Dismiss as Spam</option>
                <option value="bulk_delete">Purge Permanently</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm" style="height: 32px; font-weight: 600;">Apply</button>
        </div>

        <div class="modern-card no-padding overflow-hidden" style="border-radius: 8px; border: 1px solid #c3c4c7;">
            <table class="modern-table framework-list">
                <thead>
                    <tr>
                        <th width="40" style="padding-left: 15px;"><input type="checkbox" id="check-all"></th>
                        <th width="200">Author</th>
                        <th>Comment</th>
                        <th width="200">In Response To</th>
                        <th width="100">Status</th>
                        <th width="120" style="text-align: right; padding-right: 15px;">Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($comments)): foreach ($comments as $c): ?>
                    <tr class="framework-row <?= $c['status'] === 'pending' ? 'pending-highlight' : '' ?>">
                        <td style="padding-left: 15px;"><input type="checkbox" name="ids[]" value="<?= $c['id'] ?>" class="item-check"></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: #f0f0f1; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #50575e;">
                                    <?= strtoupper(substr($c['author_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div class="title-cell">
                                    <strong><?= h($c['author_name']) ?></strong>
                                    <small><?= h($c['author_email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px; line-height: 1.5; color: #1d2327;">
                                <?= h(substr($c['comment'], 0, 150)) ?><?= strlen($c['comment']) > 150 ? '...' : '' ?>
                            </div>
                            <div class="row-actions" style="margin-top: 8px; display: flex; gap: 15px;">
                                <?php if ($c['status'] !== 'approved'): ?>
                                    <button type="submit" name="single_approve" value="<?= $c['id'] ?>" class="act-btn approve"><i class="fas fa-check"></i> Approve</button>
                                <?php endif; ?>
                                <?php if ($c['status'] !== 'spam'): ?>
                                    <button type="submit" name="single_spam" value="<?= $c['id'] ?>" class="act-btn spam"><i class="fas fa-ban"></i> Spam</button>
                                <?php endif; ?>
                                <button type="submit" name="single_delete" value="<?= $c['id'] ?>" class="act-btn delete" onclick="return confirm('Purge this comment permanently?')"><i class="fas fa-trash-alt"></i> Delete</button>
                            </div>
                        </td>
                        <td>
                            <?php if ($c['post_title']): ?>
                                <div class="title-cell">
                                    <a href="<?= postUrl($c['post_slug'], $c['post_category_slug'], $c['post_id']) ?>" target="_blank" style="text-decoration:none; color:var(--accent-primary); font-weight:600;">
                                        <?= h(substr($c['post_title'], 0, 40)) ?><?= strlen($c['post_title']) > 40 ? '...' : '' ?>
                                    </a>
                                    <small><?= h($c['post_category'] ?? 'General') ?></small>
                                </div>
                            <?php else: ?>
                                <small class="text-muted">Target page deleted</small>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge status-<?= $c['status'] ?>"><?= strtoupper($c['status']) ?></span></td>
                        <td style="text-align: right; padding-right: 15px;">
                            <small style="color: #646970; font-size: 11px;"><?= formatDate($c['created_at']) ?></small>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" style="padding: 60px; text-align: center; color: #646970;"><i class="fas fa-comments" style="display:block; font-size: 32px; margin-bottom: 10px; opacity: 0.2;"></i> No data discovered.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
    document.getElementById('check-all').addEventListener('change', function () {
        document.querySelectorAll('.item-check').forEach(c => c.checked = this.checked);
    });
</script>

<style>
    .pending-highlight { background: #fffcf0 !important; }
    .framework-row:hover td { background: #fbfcfe !important; }
    .act-btn { background: none; border: none; padding: 0; font-size: 10px; font-weight: 700; color: #2271b1; cursor: pointer; display: flex; align-items: center; gap: 4px; opacity: 0.8; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.3px; }
    .act-btn:hover { opacity: 1; text-decoration: underline; }
    .act-btn.spam, .act-btn.delete { color: #d63638; }
    .row-actions { opacity: 0; pointer-events: none; }
    .framework-row:hover .row-actions { opacity: 1; pointer-events: auto; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>