<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

// Handle create/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCsrf()) {
        if ($_POST['action'] === 'create') {
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
        elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
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
        elseif ($_POST['action'] === 'delete') {
            requireAdmin();
            $id = intval($_POST['id']);
            if ($id) {
                db()->prepare("DELETE FROM tags WHERE id=?")->execute([$id]);
                setFlash('success', 'Tag deleted.');
            }
        }
    }
    redirect(APP_URL . '/admin/tags.php');
}

$pageTitle = 'Manage Tags';
require_once __DIR__ . '/includes/header.php';
$tags = getTagsWithCount();
?>
<div class="admin-page">
    <div class="admin-page-header">
        <h2>Tags</h2>
    </div>
    <div class="form-row">
        <div class="form-col-4">
            <div class="form-card">
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
                        <?php if (!empty($tags)):
                            foreach ($tags as $tag): ?>
                                <tr>
                                    <td>
                                        <form id="edit-tag-<?= $tag['id']?>" action="" method="POST" class="inline-edit-form" style="display:none;">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?= $tag['id']?>">
                                        </form>
                                        <input form="edit-tag-<?= $tag['id']?>" type="text" name="name" value="<?= h($tag['name'])?>" class="inline-input" placeholder="Tag Name" required style="width: 100%; border: 1px solid #ddd; padding: 6px 10px; border-radius: 6px;">
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input form="edit-tag-<?= $tag['id']?>" type="text" name="slug" value="<?= h($tag['slug'])?>" class="inline-input" placeholder="Slug" required style="width: 100%; border: 1px solid #ddd; padding: 6px 10px; border-radius: 6px;">
                                            <button form="edit-tag-<?= $tag['id']?>" type="submit" class="action-btn approve" title="Save Tag"><i class="fas fa-check"></i></button>
                                        </div>
                                    </td>
                                    <td><span class="badge"><?= $tag['post_count']?></span></td>
                                    <td>
                                        <div class="row-actions">
                                            <form action="" method="POST" class="inline-form" onsubmit="return confirm('Delete this tag permanently?')">
                                                <?php csrfField(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $tag['id']?>">
                                                <button type="submit" class="action-btn delete" title="Delete Tag"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="4">
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
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
