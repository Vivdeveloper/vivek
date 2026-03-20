<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (!empty($_POST['single_approve'])) {
        $id = intval($_POST['single_approve']);
        db()->prepare("UPDATE comments SET status='approved' WHERE id=?")->execute([$id]);
        setFlash('success', 'Comment approved.');
    }
    elseif (!empty($_POST['single_spam'])) {
        $id = intval($_POST['single_spam']);
        db()->prepare("UPDATE comments SET status='spam' WHERE id=?")->execute([$id]);
        setFlash('success', 'Marked as spam.');
    }
    elseif (!empty($_POST['single_delete'])) {
        $id = intval($_POST['single_delete']);
        db()->prepare("DELETE FROM comments WHERE id=?")->execute([$id]);
        setFlash('success', 'Comment deleted.');
    }
    else {
        $action = $_POST['action'] ?? '';
        $bulkIds = (array) ($_POST['ids'] ?? []);
        if ($bulkIds && $action) {
            $validIds = array_map('intval', $bulkIds);
            $phs = implode(',', array_fill(0, count($validIds), '?'));
            if ($action === 'bulk_approve') {
                db()->prepare("UPDATE comments SET status='approved' WHERE id IN ($phs)")->execute($validIds);
            }
            elseif ($action === 'bulk_spam') {
                db()->prepare("UPDATE comments SET status='spam' WHERE id IN ($phs)")->execute($validIds);
            }
            elseif ($action === 'bulk_delete') {
                db()->prepare("DELETE FROM comments WHERE id IN ($phs)")->execute($validIds);
            }
            setFlash('success', 'Bulk operation complete.');
        }
    }
    redirect(APP_URL . '/admin/comments.php' . (isset($_GET['status']) ? '?status=' . urlencode((string) $_GET['status']) : ''));
}

$statusFilter = $_GET['status'] ?? 'all';
$comments = getAllComments(100, $statusFilter === 'all' ? null : $statusFilter);

$counts = [
    'all' => db()->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'pending' => db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'approved' => db()->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn(),
    'spam' => db()->query("SELECT COUNT(*) FROM comments WHERE status = 'spam'")->fetchColumn(),
];

$pageTitle = 'Comments';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Comments</h2>
            <p class="text-muted">Manage user discussions and moderation queue.</p>
        </div>
    </div>

    <div class="filter-tabs-container">
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge"><?= (int) $counts['all'] ?></span></a>
            <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending <span class="badge"><?= (int) $counts['pending'] ?></span></a>
            <a href="?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved <span class="badge"><?= (int) $counts['approved'] ?></span></a>
            <a href="?status=spam" class="filter-tab <?= $statusFilter === 'spam' ? 'active' : '' ?>">Spam <span class="badge"><?= (int) $counts['spam'] ?></span></a>
        </div>
    </div>

    <form action="" method="POST" id="comments-bulk-form">
        <?php csrfField(); ?>
        <div class="bulk-actions-container">
            <select name="action" id="comments-bulk-action">
                <option value="">Bulk actions</option>
                <option value="bulk_approve">Approve</option>
                <option value="bulk_spam">Mark as spam</option>
                <option value="bulk_delete">Delete permanently</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
        </div>

        <div class="modern-card no-padding overflow-hidden">
            <table class="modern-table admin-list-table">
                <thead>
                    <tr>
                        <th class="check-column" style="width:40px;"><input type="checkbox" id="comments-check-all" aria-label="Select all"></th>
                        <th style="width:220px;">Author</th>
                        <th>Comment</th>
                        <th style="width:200px;">In response to</th>
                        <th style="width:100px;">Status</th>
                        <th style="width:120px;">Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($comments)):
                        foreach ($comments as $c):
                            $pending = ($c['status'] === 'pending');
                            $com = (string) $c['comment'];
                            $snippet = h(strlen($com) > 150 ? substr($com, 0, 150) . '…' : $com);
                            ?>
                    <tr class="admin-list-row<?= $pending ? ' admin-list-row--pending pending-highlight' : '' ?>">
                        <th class="check-column" scope="row"><input type="checkbox" name="ids[]" value="<?= (int) $c['id'] ?>" class="item-check" aria-label="Select comment"></th>
                        <td>
                            <div class="admin-list-author">
                                <div class="admin-list-avatar" aria-hidden="true"><?= strtoupper(substr($c['author_name'] ?? 'U', 0, 1)) ?></div>
                                <div class="title-cell">
                                    <strong><?= h($c['author_name'] ?: 'Anonymous') ?></strong>
                                    <?php if (!empty($c['author_email'])): ?>
                                    <span class="admin-list-meta"><?= h($c['author_email']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="admin-list-primary">
                            <div class="title-cell admin-list-stack">
                                <div class="admin-list-body"><?= $snippet ?></div>
                                <div class="row-actions admin-list-row-actions category-row-actions">
                                    <?php if ($c['status'] !== 'approved'): ?>
                                    <span class="approve"><button type="submit" name="single_approve" value="<?= (int) $c['id'] ?>" class="admin-list-action posts-row-action-btn">Approve</button></span>
                                    <span class="row-actions-sep">|</span>
                                    <?php endif; ?>
                                    <?php if ($c['status'] !== 'spam'): ?>
                                    <span class="spam"><button type="submit" name="single_spam" value="<?= (int) $c['id'] ?>" class="admin-list-action posts-row-action-btn">Spam</button></span>
                                    <span class="row-actions-sep">|</span>
                                    <?php endif; ?>
                                    <span class="delete"><button type="submit" name="single_delete" value="<?= (int) $c['id'] ?>"
                                            class="admin-list-action admin-list-action--danger posts-row-action-btn posts-row-action-btn--danger"
                                            onclick="return confirm('Delete this comment permanently?');">Delete</button></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($c['post_title'])): ?>
                            <div class="title-cell">
                                <a href="<?= h(postUrl($c['post_slug'], $c['post_category_slug'] ?? null, $c['post_id'])) ?>" target="_blank" rel="noopener" class="admin-list-response-link">
                                    <?= h(strlen($c['post_title']) > 40 ? substr($c['post_title'], 0, 40) . '…' : $c['post_title']) ?>
                                </a>
                                <span class="admin-list-meta"><?= h($c['post_category'] ?? 'General') ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:12px;">Post removed</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge status-<?= h($c['status']) ?>"><?= strtoupper(h($c['status'])) ?></span></td>
                        <td><small class="text-muted"><?= h(formatDate($c['created_at'])) ?></small></td>
                    </tr>
                    <?php
                        endforeach;
                    else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-row-state">
                                <i class="fas fa-comments"></i>
                                <p>No comments yet.</p>
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
(function () {
    var form = document.getElementById('comments-bulk-form');
    var all = document.getElementById('comments-check-all');
    if (all && form) {
        all.addEventListener('change', function () {
            form.querySelectorAll('.item-check').forEach(function (c) { c.checked = all.checked; });
        });
    }
    if (form) {
        form.addEventListener('submit', function (e) {
            var sel = document.getElementById('comments-bulk-action');
            if (!sel || !sel.value) {
                e.preventDefault();
                return;
            }
            var any = false;
            form.querySelectorAll('.item-check').forEach(function (c) { if (c.checked) any = true; });
            if (!any) e.preventDefault();
        });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
