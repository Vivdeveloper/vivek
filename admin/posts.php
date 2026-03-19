<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

$_currentUser = currentUser();
$postType = $_GET['type'] ?? 'post';

// Get CPT Details if not standard post
$cptName = 'Posts';
if ($postType !== 'post') {
    $cptDetails = db()->prepare("SELECT name FROM custom_post_types WHERE slug = ?");
    $cptDetails->execute([$postType]);
    $cptData = $cptDetails->fetch();
    if ($cptData) {
        $cptName = $cptData['name'];
    } else {
        $postType = 'post'; // fallback
    }
}

// Handling single and bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (!empty($_POST['single_trash'])) {
        $id = intval($_POST['single_trash']);
        db()->prepare("UPDATE posts SET status = 'trash' WHERE id = ?")->execute([$id]);
        setFlash('success', 'Item moved to trash.');
    } elseif (!empty($_POST['single_restore'])) {
        $id = intval($_POST['single_restore']);
        db()->prepare("UPDATE posts SET status = 'draft' WHERE id = ?")->execute([$id]);
        setFlash('success', 'Item restored to drafts.');
    } elseif (!empty($_POST['single_delete'])) {
        requireAdmin();
        $id = intval($_POST['single_delete']);
        db()->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
        setFlash('success', 'Item permanently deleted.');
    } elseif (!empty($_POST['single_duplicate'])) {
        $id = intval($_POST['single_duplicate']);
        $stmt = db()->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $original = $stmt->fetch();
        if ($original) {
            $newTitle = $original['title'] . ' (Copy)';
            $newSlug = $original['slug'] . '-copy-' . time();
            db()->prepare("INSERT INTO posts (title, slug, content, category_id, author_id, featured_image, status, post_type) VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)")
                ->execute([$newTitle, $newSlug, $original['content'], $original['category_id'], $_currentUser['id'], $original['featured_image'], $original['post_type']]);
            setFlash('success', 'Item duplicated as draft.');
        }
    } else {
        $action = $_POST['action'] ?? '';
        $bulkIds = $_POST['ids'] ?? [];
        if ($bulkIds && $action) {
            $validIds = array_map('intval', $bulkIds);
            if ($validIds) {
                $placeholders = implode(',', array_fill(0, count($validIds), '?'));
                if ($action === 'bulk_publish') {
                    db()->prepare("UPDATE posts SET status='published' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' items published.');
                } elseif ($action === 'bulk_draft') {
                    db()->prepare("UPDATE posts SET status='draft' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' items moved to drafts.');
                } elseif ($action === 'bulk_trash') {
                    db()->prepare("UPDATE posts SET status='trash' WHERE id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' items moved to trash.');
                } elseif ($action === 'bulk_restore') {
                    db()->prepare("UPDATE posts SET status='draft' WHERE status='trash' AND id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' items restored.');
                } elseif ($action === 'bulk_delete') {
                    requireAdmin();
                    db()->prepare("DELETE FROM posts WHERE status='trash' AND id IN ($placeholders)")->execute($validIds);
                    setFlash('success', count($validIds) . ' items permanently deleted.');
                }
            }
        }
    }
    header('Location: ' . APP_URL . '/admin/posts.php?type=' . $postType . (isset($_GET['status']) ? '&status=' . $_GET['status'] : ''));
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug 
        FROM posts p 
        LEFT JOIN users u ON p.author_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.post_type = " . db()->quote($postType);

if ($statusFilter !== 'all') {
    $sql .= " AND p.status = " . db()->quote($statusFilter);
} else {
    $sql .= " AND p.status != 'trash'";
}
$sql .= " ORDER BY p.created_at DESC LIMIT 100";

$posts = db()->query($sql)->fetchAll();

// Get counts for tabs dynamically for the specific post type
$counts = [
    'all'       => db()->prepare("SELECT COUNT(*) FROM posts WHERE post_type = ? AND status != 'trash'"),
    'published' => db()->prepare("SELECT COUNT(*) FROM posts WHERE post_type = ? AND status = 'published'"),
    'draft'     => db()->prepare("SELECT COUNT(*) FROM posts WHERE post_type = ? AND status = 'draft'"),
    'trash'     => db()->prepare("SELECT COUNT(*) FROM posts WHERE post_type = ? AND status = 'trash'")
];

$counts['all']->execute([$postType]); $allCount = $counts['all']->fetchColumn();
$counts['published']->execute([$postType]); $pubCount = $counts['published']->fetchColumn();
$counts['draft']->execute([$postType]); $draftCount = $counts['draft']->fetchColumn();
$counts['trash']->execute([$postType]); $trashCount = $counts['trash']->fetchColumn();

$pageTitle = 'Manage ' . h($cptName);
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2><?= h($cptName) ?></h2>
            <p class="text-muted">Manage your <?= strtolower(h($cptName)) ?> library</p>
        </div>
        <div class="header-actions">
            <a href="<?= APP_URL ?>/admin/post-create.php?type=<?= $postType ?>" class="btn btn-primary"><i class="fas fa-plus"></i> New <?= h($cptName) ?></a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs-container">
        <div class="filter-tabs">
            <a href="?type=<?= h($postType) ?>&status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge"><?= $allCount ?></span></a>
            <a href="?type=<?= h($postType) ?>&status=published" class="filter-tab <?= $statusFilter === 'published' ? 'active' : '' ?>">Published <span class="badge"><?= $pubCount ?></span></a>
            <a href="?type=<?= h($postType) ?>&status=draft" class="filter-tab <?= $statusFilter === 'draft' ? 'active' : '' ?>">Drafts <span class="badge"><?= $draftCount ?></span></a>
            <a href="?type=<?= h($postType) ?>&status=trash" class="filter-tab <?= $statusFilter === 'trash' ? 'active' : '' ?>">Trash <span class="badge"><?= $trashCount ?></span></a>
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
                        <th style="width: auto;">Title</th>
                        <th style="width: 150px;">Category</th>
                        <th style="width: 100px;">Author</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 110px;">Date</th>
                        <th width="150" style="text-align: right;">Actions</th>
                    </tr>
                </thead>
            <tbody>
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $post['id'] ?>" class="item-check"></td>
                        <td>
                            <div class="title-cell">
                                <strong><?= h($post['title']) ?></strong>
                                <small><?= truncate($post['content'], 40) ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="category-tag"><?= h($post['category_name'] ?? 'Uncategorized') ?></span>
                        </td>
                        <td><small><?= h($post['author_name']) ?></small></td>
                        <td>
                            <span class="status-badge status-<?= $post['status'] ?>">
                                <?= strtoupper($post['status']) ?>
                            </span>
                        </td>
                        <td><small><?= formatDate($post['created_at']) ?></small></td>
                        <td style="text-align: right;">
                            <div class="row-actions">
                                <?php if ($post['status'] === 'trash'): ?>
                                    <button type="submit" name="single_restore" value="<?= $post['id'] ?>" class="action-btn restore" title="Restore to Draft"><i class="fas fa-undo"></i></button>
                                    <button type="submit" name="single_delete" value="<?= $post['id'] ?>" class="action-btn delete" title="Delete Permanently" onclick="return confirm('PERMANENTLY DELETE this post? This cannot be undone.')"><i class="fas fa-trash-alt"></i></button>
                                <?php else: ?>
                                    <a href="<?= postUrl($post['slug'], $post['category_slug'] ?? null, $post['id']) ?>" class="action-btn" target="_blank" title="View"><i class="fas fa-external-link-alt"></i></a>
                                    <a href="<?= APP_URL ?>/admin/post-edit.php?id=<?= $post['id'] ?>&type=<?= h($postType) ?>" class="action-btn edit" title="Edit"><i class="fas fa-pen"></i></a>
                                    <button type="submit" name="single_duplicate" value="<?= $post['id'] ?>" class="action-btn duplicate" title="Duplicate"><i class="fas fa-copy"></i></button>
                                    <button type="submit" name="single_trash" value="<?= $post['id'] ?>" class="action-btn trash" title="Move to Trash" onclick="return confirm('Move this item to Trash?')"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-row-state">
                                <i class="fas fa-newspaper"></i>
                                <p>No posts found in this category.</p>
                                <a href="<?= APP_URL ?>/admin/post-create.php">Create your first post</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </form>

<script>
document.getElementById('check-all') && document.getElementById('check-all').addEventListener('change', function() {
    const checks = document.querySelectorAll('.item-check');
    checks.forEach(c => c.checked = this.checked);
});
</script>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
