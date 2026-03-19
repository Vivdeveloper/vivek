<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

// Handling Trash/Restore logic directly in pages.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (!empty($_POST['single_trash'])) {
        $id = intval($_POST['single_trash']);
        db()->prepare("UPDATE pages SET status = 'trash' WHERE id = ?")->execute([$id]);
        setFlash('success', 'Page moved to trash.');
    } elseif (!empty($_POST['single_restore'])) {
        $id = intval($_POST['single_restore']);
        db()->prepare("UPDATE pages SET status = 'draft' WHERE id = ?")->execute([$id]);
        setFlash('success', 'Page restored to drafts.');
    } elseif (!empty($_POST['single_delete'])) {
        requireAdmin();
        $id = intval($_POST['single_delete']);
        db()->prepare("DELETE FROM pages WHERE id = ?")->execute([$id]);
        setFlash('success', 'Page permanently deleted.');
    } else {
        $action = $_POST['action'] ?? '';
        $bulkIds = $_POST['ids'] ?? [];
        if ($bulkIds && $action) {
            $validIds = array_map('intval', $bulkIds);
            if ($validIds) {
                $placeholders = implode(',', array_fill(0, count($validIds), '?'));
                if ($action === 'bulk_publish') {
                    db()->prepare("UPDATE pages SET status='published' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' pages published.');
                } elseif ($action === 'bulk_draft') {
                    db()->prepare("UPDATE pages SET status='draft' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' pages moved to drafts.');
                } elseif ($action === 'bulk_trash') {
                    db()->prepare("UPDATE pages SET status='trash' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' pages moved to trash.');
                } elseif ($action === 'bulk_restore') {
                    db()->prepare("UPDATE pages SET status='draft' WHERE status='trash' AND id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' pages restored.');
                } elseif ($action === 'bulk_delete') {
                    requireAdmin();
                    db()->prepare("DELETE FROM pages WHERE status='trash' AND id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' pages permanently deleted.');
                }
            }
        }
    }
    header('Location: ' . APP_URL . '/admin/pages.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT * FROM pages";

if ($statusFilter !== 'all') {
    $sql .= " WHERE status = " . db()->quote($statusFilter);
} else {
    $sql .= " WHERE status != 'trash'";
}
$sql .= " ORDER BY created_at DESC";

$pages = db()->query($sql)->fetchAll();

// Get counts for tabs
$counts = [
    'all'       => db()->query("SELECT COUNT(*) FROM pages WHERE status != 'trash'")->fetchColumn(),
    'published' => db()->query("SELECT COUNT(*) FROM pages WHERE status = 'published'")->fetchColumn(),
    'draft'     => db()->query("SELECT COUNT(*) FROM pages WHERE status = 'draft'")->fetchColumn(),
    'trash'     => db()->query("SELECT COUNT(*) FROM pages WHERE status = 'trash'")->fetchColumn(),
];

$pageTitle = 'Manage Pages';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Manage Pages</h2>
            <p class="text-muted">Create layout and static content</p>
        </div>
        <div class="header-actions">
            <a href="<?= APP_URL ?>/admin/page-create.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Page</a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs-container">
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge"><?= $counts['all'] ?></span></a>
            <a href="?status=published" class="filter-tab <?= $statusFilter === 'published' ? 'active' : '' ?>">Published <span class="badge"><?= $counts['published'] ?></span></a>
            <a href="?status=draft" class="filter-tab <?= $statusFilter === 'draft' ? 'active' : '' ?>">Drafts <span class="badge"><?= $counts['draft'] ?></span></a>
            <a href="?status=trash" class="filter-tab <?= $statusFilter === 'trash' ? 'active' : '' ?>">Trash <span class="badge"><?= $counts['trash'] ?></span></a>
        </div>
    </div>

    <form action="" method="POST" id="bulk-form">
        <?php csrfField(); ?>
        <div class="bulk-actions-container">
            <select name="action">
                <option value="">Bulk Actions</option>
                <?php if ($statusFilter !== 'trash'): ?>
                    <option value="bulk_publish">Publish</option>
                    <option value="bulk_draft">Move to Drafts</option>
                    <option value="bulk_trash">Move to Trash</option>
                <?php else: ?>
                    <option value="bulk_restore">Restore from Trash</option>
                    <option value="bulk_delete">Delete Permanently</option>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
        </div>

    <div class="modern-card no-padding overflow-hidden">
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="check-all"></th>
                    <th style="width: auto;">Title & Path</th>
                    <th style="width: 130px;">Design</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 110px;">Updated</th>
                    <th width="150" style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pages)): ?>
                    <?php foreach ($pages as $p): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" class="item-check"></td>
                        <td>
                            <div class="title-cell">
                                <strong><?= h($p['title']) ?></strong>
                                <small><code>/<?= h($p['slug']) ?></code></small>
                            </div>
                        </td>
                        <td>
                            <div class="design-badges">
                                <?php if (!empty($p['custom_css'])): ?>
                                    <span class="design-badge css" title="Has Custom CSS"><i class="fas fa-code"></i> CSS</span>
                                <?php endif; ?>
                                <?php if (!empty($p['featured_image'])): ?>
                                    <span class="design-badge img" title="Has Featured Image"><i class="fas fa-image"></i> Image</span>
                                <?php endif; ?>
                                <?php if (empty($p['custom_css']) && empty($p['featured_image'])): ?>
                                    <small class="text-muted">Standard</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $p['status'] ?>">
                                <?= strtoupper($p['status']) ?>
                            </span>
                        </td>
                        <td><small><?= formatDate($p['updated_at'] ?? $p['created_at']) ?></small></td>
                        <td style="text-align: right;">
                            <div class="row-actions">
                                <?php if ($p['status'] === 'trash'): ?>
                                    <button type="submit" name="single_restore" value="<?= $p['id'] ?>" class="action-btn restore" title="Restore to Draft"><i class="fas fa-undo"></i></button>
                                    <button type="submit" name="single_delete" value="<?= $p['id'] ?>" class="action-btn delete" title="Delete Permanently" onclick="return confirm('PERMANENTLY DELETE this page?')"><i class="fas fa-trash-alt"></i></button>
                                <?php else: ?>
                                    <a href="<?= pageUrl($p['slug'], $p['id']) ?>" class="action-btn" target="_blank" title="View"><i class="fas fa-external-link-alt"></i></a>
                                    <a href="<?= APP_URL ?>/admin/page-edit.php?id=<?= $p['id'] ?>" class="action-btn edit" title="Edit"><i class="fas fa-pen"></i></a>
                                    <button type="submit" name="single_trash" value="<?= $p['id'] ?>" class="action-btn trash" title="Move to Trash" onclick="return confirm('Move this page to Trash?')"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-row-state">
                                <i class="fas fa-file-alt"></i>
                                <p>No pages found in this category.</p>
                                <a href="<?= APP_URL ?>/admin/page-create.php">Create your first page</a>
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
document.getElementById('check-all') && document.getElementById('check-all').addEventListener('change', function() {
    const checks = document.querySelectorAll('.item-check');
    checks.forEach(c => c.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
