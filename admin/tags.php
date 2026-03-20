<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCsrf()) {
        $action = $_POST['action'];

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                try {
                    db()->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)")->execute([$name, slugify($name)]);
                    setFlash('success', 'Tag created!');
                }
                catch (PDOException $e) {
                    setFlash('error', 'Tag already exists.');
                }
            }
        }
        elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            if (!$slug) {
                $slug = $name;
            }
            if ($id && $name) {
                db()->prepare("UPDATE tags SET name=?, slug=? WHERE id=?")->execute([$name, slugify($slug), $id]);
                setFlash('success', 'Tag updated!');
            }
        }
        elseif ($action === 'delete') {
            requireAdmin();
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                db()->prepare("DELETE FROM post_tags WHERE tag_id = ?")->execute([$id]);
                db()->prepare("DELETE FROM tags WHERE id = ?")->execute([$id]);
                setFlash('success', 'Tag deleted.');
            }
        }
        elseif ($action === 'bulk_delete' && isAdmin()) {
            $ids = $_POST['ids'] ?? [];
            $validIds = array_filter(array_map('intval', (array) $ids));
            if ($validIds) {
                $placeholders = implode(',', array_fill(0, count($validIds), '?'));
                db()->prepare("DELETE FROM post_tags WHERE tag_id IN ($placeholders)")->execute($validIds);
                db()->prepare("DELETE FROM tags WHERE id IN ($placeholders)")->execute($validIds);
                setFlash('success', count($validIds) === 1 ? '1 tag deleted.' : count($validIds) . ' tags deleted.');
            }
        }
    }
    redirect(APP_URL . '/admin/tags.php');
}

$pageTitle = 'Manage Tags';
require_once __DIR__ . '/includes/header.php';

$tags = getTagsWithCount();
$editTag = null;
if (isset($_GET['edit'])) {
    $eid = (int) $_GET['edit'];
    if ($eid) {
        $stmt = db()->prepare("SELECT * FROM tags WHERE id = ?");
        $stmt->execute([$eid]);
        $editTag = $stmt->fetch() ?: null;
    }
}

$isAdmin = isAdmin();
$listColspan = $isAdmin ? 4 : 3;
?>
<div class="admin-page">
    <div class="admin-page-header">
        <h2>Tags</h2>
    </div>
    <div class="form-row">
        <div class="form-col-4">
            <div class="form-card">
                <?php if ($editTag): ?>
                <h3>Edit Tag</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= (int) $editTag['id'] ?>">
                    <div class="form-group">
                        <label for="edit-name">Name</label>
                        <input type="text" id="edit-name" name="name" value="<?= h($editTag['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-slug">Slug</label>
                        <input type="text" id="edit-slug" name="slug" value="<?= h($editTag['slug']) ?>">
                    </div>
                    <div class="category-form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Update</button>
                        <a href="<?= APP_URL ?>/admin/tags.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <h3>Add New Tag</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label for="name">Tag Name *</label>
                        <input type="text" id="name" name="name" placeholder="Enter tag name" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add Tag</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-col-8">
            <?php if ($isAdmin): ?>
            <form action="" method="POST" id="tags-bulk-form">
                <?php csrfField(); ?>
                <div class="bulk-actions-container">
                    <select name="action" id="tags-bulk-action">
                        <option value="">Bulk actions</option>
                        <option value="bulk_delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-outline btn-sm" id="tags-bulk-apply">Apply</button>
                </div>
            <?php endif; ?>

                <div class="modern-card no-padding overflow-hidden">
                    <table class="modern-table admin-list-table category-list-table tag-list-table">
                        <thead>
                            <tr>
                                <?php if ($isAdmin): ?>
                                <th class="check-column" scope="col"><input type="checkbox" id="tags-check-all" aria-label="Select all"></th>
                                <?php endif; ?>
                                <th scope="col">Name</th>
                                <th scope="col">Slug</th>
                                <th scope="col" class="column-count">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tags)):
                                foreach ($tags as $tag): ?>
                            <tr class="tag-list-row" data-tag-id="<?= (int) $tag['id'] ?>">
                                <?php if ($isAdmin): ?>
                                <th class="check-column" scope="row">
                                    <input type="checkbox" name="ids[]" value="<?= (int) $tag['id'] ?>" class="tag-item-check" aria-label="Select <?= h($tag['name']) ?>">
                                </th>
                                <?php endif; ?>
                                <td class="column-name admin-list-primary">
                                    <div class="title-cell category-title-cell admin-list-stack">
                                        <strong><a href="<?= APP_URL ?>/admin/tags.php?edit=<?= (int) $tag['id'] ?>" class="admin-list-primary-link category-title-link"><?= h($tag['name']) ?></a></strong>
                                        <div class="row-actions admin-list-row-actions category-row-actions">
                                            <span class="edit"><a href="<?= APP_URL ?>/admin/tags.php?edit=<?= (int) $tag['id'] ?>">Edit</a></span>
                                            <span class="row-actions-sep">|</span>
                                            <span class="quick-edit"><button type="button" class="category-quick-edit-btn tag-quick-edit-btn">Quick&nbsp;Edit</button></span>
                                            <?php if ($isAdmin): ?>
                                            <span class="row-actions-sep">|</span>
                                            <span class="delete">
                                                <button type="submit" class="row-action-link-btn" form="tag-del-<?= (int) $tag['id'] ?>"
                                                    onclick="return confirm('Delete this tag? It will be removed from all posts.');">Delete</button>
                                            </span>
                                            <?php endif; ?>
                                            <span class="row-actions-sep">|</span>
                                            <span class="view"><a href="<?= tagUrl($tag['slug']) ?>" target="_blank" rel="noopener">View</a></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="column-slug"><code class="category-slug-display"><?= h($tag['slug']) ?></code></td>
                                <td class="column-count"><?= (int) $tag['post_count'] ?></td>
                            </tr>
                            <tr class="category-quick-edit-row tag-quick-edit-row" id="tag-quick-<?= (int) $tag['id'] ?>" hidden>
                                <td colspan="<?= $listColspan ?>" class="category-quick-edit-cell">
                                    <div class="category-quick-edit-panel">
                                        <div class="category-quick-edit-fields">
                                            <div class="form-group category-quick-field">
                                                <label for="tag-qe-name-<?= (int) $tag['id'] ?>">Name</label>
                                                <input type="text" id="tag-qe-name-<?= (int) $tag['id'] ?>" name="name" form="tag-qe-<?= (int) $tag['id'] ?>"
                                                    value="<?= h($tag['name']) ?>" required>
                                            </div>
                                            <div class="form-group category-quick-field">
                                                <label for="tag-qe-slug-<?= (int) $tag['id'] ?>">Slug</label>
                                                <input type="text" id="tag-qe-slug-<?= (int) $tag['id'] ?>" name="slug" form="tag-qe-<?= (int) $tag['id'] ?>"
                                                    value="<?= h($tag['slug']) ?>">
                                            </div>
                                        </div>
                                        <div class="category-quick-edit-buttons">
                                            <button type="submit" class="btn btn-primary btn-sm" form="tag-qe-<?= (int) $tag['id'] ?>">Update</button>
                                            <button type="button" class="btn btn-outline btn-sm category-quick-cancel tag-quick-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                endforeach;
                            else: ?>
                            <tr>
                                <td colspan="<?= $listColspan ?>">
                                    <div class="empty-row-state">
                                        <i class="fas fa-tags"></i>
                                        <p>No tags yet</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php if ($isAdmin): ?></form><?php endif; ?>

            <?php if (!empty($tags)):
                foreach ($tags as $tag):
                    $tid = (int) $tag['id']; ?>
            <form id="tag-qe-<?= $tid ?>" method="POST" class="category-ghost-form" aria-hidden="true">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $tid ?>">
            </form>
            <?php if ($isAdmin): ?>
            <form id="tag-del-<?= $tid ?>" method="POST" class="category-ghost-form" aria-hidden="true">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $tid ?>">
            </form>
            <?php endif; ?>
            <?php
                endforeach;
            endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    var bulkForm = document.getElementById('tags-bulk-form');
    var checkAll = document.getElementById('tags-check-all');
    if (checkAll && bulkForm) {
        checkAll.addEventListener('change', function () {
            bulkForm.querySelectorAll('.tag-item-check').forEach(function (c) { c.checked = checkAll.checked; });
        });
    }
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            var sel = document.getElementById('tags-bulk-action');
            if (!sel || !sel.value) {
                e.preventDefault();
                return;
            }
            if (sel.value !== 'bulk_delete') return;
            var any = false;
            bulkForm.querySelectorAll('.tag-item-check').forEach(function (c) { if (c.checked) any = true; });
            if (!any) {
                e.preventDefault();
                return;
            }
            if (!confirm('Delete the selected tags? They will be removed from all posts.')) e.preventDefault();
        });
    }
    document.querySelectorAll('.tag-list-row').forEach(function (row) {
        var id = row.getAttribute('data-tag-id');
        var qeRow = document.getElementById('tag-quick-' + id);
        if (!qeRow) return;
        var btn = row.querySelector('.tag-quick-edit-btn');
        var cancel = qeRow.querySelector('.tag-quick-cancel');
        function closeAll() {
            document.querySelectorAll('.tag-quick-edit-row').forEach(function (r) { r.hidden = true; });
        }
        if (btn) {
            btn.addEventListener('click', function () {
                var open = !qeRow.hidden;
                closeAll();
                if (!open) qeRow.hidden = false;
            });
        }
        if (cancel) cancel.addEventListener('click', function () { qeRow.hidden = true; });
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
