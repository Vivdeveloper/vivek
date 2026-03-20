<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

// Handle create/edit/delete/bulk (before output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCsrf()) {
        $action = $_POST['action'];

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                try {
                    db()->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")->execute([$name, slugify($name)]);
                    setFlash('success', 'Category created!');
                }
                catch (PDOException $e) {
                    setFlash('error', 'Category already exists.');
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
                db()->prepare("UPDATE categories SET name=?, slug=? WHERE id=?")->execute([$name, slugify($slug), $id]);
                setFlash('success', 'Category updated!');
            }
        }
        elseif ($action === 'delete') {
            requireAdmin();
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                db()->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
                setFlash('success', 'Category deleted.');
            }
        }
        elseif ($action === 'bulk_delete' && isAdmin()) {
            $ids = $_POST['ids'] ?? [];
            $validIds = array_filter(array_map('intval', (array)$ids));
            if ($validIds) {
                $placeholders = implode(',', array_fill(0, count($validIds), '?'));
                db()->prepare("DELETE FROM categories WHERE id IN ($placeholders)")->execute($validIds);
                setFlash('success', count($validIds) === 1 ? '1 category deleted.' : count($validIds) . ' categories deleted.');
            }
        }
    }
    redirect(APP_URL . '/admin/categories.php');
}

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/includes/header.php';

$categories = getCategoriesWithCount();
$editCategory = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid) {
        $stmt = db()->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$eid]);
        $editCategory = $stmt->fetch() ?: null;
    }
}

$isAdmin = isAdmin();
$listColspan = $isAdmin ? 4 : 3;
?>
<div class="admin-page">
    <div class="admin-page-header">
        <h2>Categories</h2>
    </div>
    <div class="form-row">
        <div class="form-col-4">
            <div class="form-card">
                <?php if ($editCategory): ?>
                <h3>Edit Category</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?=(int)$editCategory['id']?>">
                    <div class="form-group">
                        <label for="edit-name">Name</label>
                        <input type="text" id="edit-name" name="name" value="<?= h($editCategory['name'])?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-slug">Slug</label>
                        <input type="text" id="edit-slug" name="slug" value="<?= h($editCategory['slug'])?>">
                    </div>
                    <div class="category-form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Update</button>
                        <a href="<?= APP_URL?>/admin/categories.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
                <?php
else: ?>
                <h3>Add New Category</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" placeholder="Enter category name" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add
                        Category</button>
                </form>
                <?php
endif; ?>
            </div>
        </div>
        <div class="form-col-8">
            <?php if ($isAdmin): ?>
            <form action="" method="POST" id="categories-bulk-form">
                <?php csrfField(); ?>
                <div class="bulk-actions-container">
                    <select name="action" id="categories-bulk-action">
                        <option value="">Bulk actions</option>
                        <option value="bulk_delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-outline btn-sm" id="categories-bulk-apply">Apply</button>
                </div>
                <?php
endif; ?>

                <div class="modern-card no-padding overflow-hidden">
                    <table class="modern-table category-list-table">
                        <thead>
                            <tr>
                                <?php if ($isAdmin): ?>
                                <th class="check-column" scope="col"><input type="checkbox" id="categories-check-all"
                                        aria-label="Select all"></th>
                                <?php
endif; ?>
                                <th scope="col">Name</th>
                                <th scope="col">Slug</th>
                                <th scope="col" class="column-count">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)):
    foreach ($categories as $cat): ?>
                            <tr class="category-list-row" data-category-id="<?=(int)$cat['id']?>">
                                <?php if ($isAdmin): ?>
                                <th class="check-column" scope="row">
                                    <input type="checkbox" name="ids[]" value="<?=(int)$cat['id']?>"
                                        class="category-item-check" aria-label="Select <?= h($cat['name'])?>">
                                </th>
                                <?php
        endif; ?>
                                <td class="column-name">
                                    <div class="title-cell category-title-cell">
                                        <strong><a
                                                href="<?= APP_URL?>/admin/categories.php?edit=<?=(int)$cat['id']?>"
                                                class="category-title-link">
                                                <?= h($cat['name'])?>
                                            </a></strong>
                                        <div class="row-actions category-row-actions">
                                            <span class="edit"><a
                                                    href="<?= APP_URL?>/admin/categories.php?edit=<?=(int)$cat['id']?>">Edit</a></span>
                                            <span class="row-actions-sep">|</span>
                                            <span class="quick-edit"><button type="button"
                                                    class="category-quick-edit-btn">Quick&nbsp;Edit</button></span>
                                            <?php if ($isAdmin): ?>
                                            <span class="row-actions-sep">|</span>
                                            <span class="delete">
                                                <button type="submit" class="row-action-link-btn"
                                                    form="category-del-<?=(int)$cat['id']?>"
                                                    onclick="return confirm('Delete this category permanently?');">Delete</button>
                                            </span>
                                            <?php
        endif; ?>
                                            <span class="row-actions-sep">|</span>
                                            <span class="view"><a href="<?= categoryUrl($cat['slug'])?>"
                                                    target="_blank" rel="noopener">View</a></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="column-slug"><code
                                        class="category-slug-display"><?= h($cat['slug'])?></code></td>
                                <td class="column-count">
                                    <?=(int)$cat['post_count']?>
                                </td>
                            </tr>
                            <tr class="category-quick-edit-row" id="category-quick-<?=(int)$cat['id']?>" hidden>
                                <td colspan="<?= $listColspan?>" class="category-quick-edit-cell">
                                    <div class="category-quick-edit-panel">
                                        <div class="category-quick-edit-fields">
                                            <div class="form-group category-quick-field">
                                                <label for="qe-name-<?=(int)$cat['id']?>">Name</label>
                                                <input type="text" id="qe-name-<?=(int)$cat['id']?>" name="name"
                                                    form="category-qe-<?=(int)$cat['id']?>"
                                                    value="<?= h($cat['name'])?>" required>
                                            </div>
                                            <div class="form-group category-quick-field">
                                                <label for="qe-slug-<?=(int)$cat['id']?>">Slug</label>
                                                <input type="text" id="qe-slug-<?=(int)$cat['id']?>" name="slug"
                                                    form="category-qe-<?=(int)$cat['id']?>"
                                                    value="<?= h($cat['slug'])?>">
                                            </div>
                                        </div>
                                        <div class="category-quick-edit-buttons">
                                            <button type="submit" class="btn btn-primary btn-sm"
                                                form="category-qe-<?=(int)$cat['id']?>">Update</button>
                                            <button type="button"
                                                class="btn btn-outline btn-sm category-quick-cancel">Cancel</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php
    endforeach;
else: ?>
                            <tr>
                                <td colspan="<?= $listColspan?>">
                                    <div class="empty-row-state">
                                        <i class="fas fa-folder-open"></i>
                                        <p>No categories yet</p>
                                    </div>
                                </td>
                            </tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($isAdmin): ?>
            </form>
            <?php
endif; ?>

            <?php if (!empty($categories)):
    foreach ($categories as $cat):
        $cid = (int)$cat['id']; ?>
            <form id="category-qe-<?= $cid?>" method="POST" class="category-ghost-form" aria-hidden="true">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $cid?>">
            </form>
            <?php if ($isAdmin): ?>
            <form id="category-del-<?= $cid?>" method="POST" class="category-ghost-form" aria-hidden="true">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $cid?>">
            </form>
            <?php
        endif; ?>
            <?php
    endforeach;
endif; ?>
        </div>
    </div>
</div>
<script>
    (function () {
        var bulkForm = document.getElementById('categories-bulk-form');
        var checkAll = document.getElementById('categories-check-all');
        if (checkAll && bulkForm) {
            checkAll.addEventListener('change', function () {
                bulkForm.querySelectorAll('.category-item-check').forEach(function (c) { c.checked = checkAll.checked; });
            });
        }
        if (bulkForm) {
            bulkForm.addEventListener('submit', function (e) {
                var sel = document.getElementById('categories-bulk-action');
                if (!sel || !sel.value) {
                    e.preventDefault();
                    return;
                }
                if (sel.value !== 'bulk_delete') return;
                var any = false;
                bulkForm.querySelectorAll('.category-item-check').forEach(function (c) { if (c.checked) any = true; });
                if (!any) {
                    e.preventDefault();
                    return;
                }
                if (!confirm('Delete the selected categories? Posts will become uncategorized.')) e.preventDefault();
            });
        }
        document.querySelectorAll('.category-list-row').forEach(function (row) {
            var id = row.getAttribute('data-category-id');
            var qeRow = document.getElementById('category-quick-' + id);
            if (!qeRow) return;
            var btn = row.querySelector('.category-quick-edit-btn');
            var cancel = qeRow.querySelector('.category-quick-cancel');
            function closeAll() {
                document.querySelectorAll('.category-quick-edit-row').forEach(function (r) { r.hidden = true; });
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