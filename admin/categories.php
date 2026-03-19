<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

// Handle create/edit/delete (Before any output to avoid headers already sent)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCsrf()) {
        if ($_POST['action'] === 'create') {
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
        elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
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
        elseif ($_POST['action'] === 'delete') {
            requireAdmin();
            $id = intval($_POST['id']);
            if ($id) {
                db()->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
                setFlash('success', 'Category deleted.');
            }
        }
    }
    redirect(APP_URL . '/admin/categories.php');
}

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/includes/header.php';
$categories = getCategoriesWithCount();
?>
<div class="admin-page">
    <div class="admin-page-header">
        <h2>Categories</h2>
    </div>
    <div class="form-row">
        <div class="form-col-4">
            <div class="form-card">
                <h3>Add New Category</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="form-group"><label for="name">Category Name *</label><input type="text" id="name"
                            name="name" placeholder="Enter category name" required></div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add
                        Category</button>
                </form>
            </div>
        </div>
        <div class="form-col-8">
            <div class="modern-card no-padding overflow-hidden">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Posts</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)):
    foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <form id="edit-cat-<?= $cat['id']?>" action="" method="POST" class="inline-edit-form"
                                    style="display:none;">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $cat['id']?>">
                                </form>
                                <input form="edit-cat-<?= $cat['id']?>" type="text" name="name"
                                    value="<?= h($cat['name'])?>" class="inline-input" placeholder="Category Name"
                                    required
                                    style="width: 100%; border: 1px solid #ddd; padding: 6px 10px; border-radius: 6px;">
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input form="edit-cat-<?= $cat['id']?>" type="text" name="slug"
                                        value="<?= h($cat['slug'])?>" class="inline-input" placeholder="Slug" required
                                        style="width: 100%; border: 1px solid #ddd; padding: 6px 10px; border-radius: 6px;">
                                    <button form="edit-cat-<?= $cat['id']?>" type="submit" class="action-btn approve"
                                        title="Save Category"><i class="fas fa-check"></i></button>
                                </div>
                            </td>
                            <td><span class="badge">
                                    <?= $cat['post_count']?>
                                </span></td>
                            <td>
                                <div class="row-actions">
                                    <form action="" method="POST" class="inline-form"
                                        onsubmit="return confirm('Delete this category permanently?')">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $cat['id']?>">
                                        <button type="submit" class="action-btn delete" title="Delete Category"><i
                                                class="fas fa-trash-alt"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php
    endforeach;
else: ?>
                        <tr>
                            <td colspan="4">
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
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>