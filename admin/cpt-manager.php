<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && verifyCsrf()) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = slugify($name);
        $icon = trim($_POST['icon'] ?? 'fas fa-file-alt');

        if ($name && $slug) {
            try {
                db()->prepare("INSERT INTO custom_post_types (name, slug, icon) VALUES (?, ?, ?)")
                    ->execute([$name, $slug, $icon]);
                setFlash('success', 'Custom Post Type created successfully!');
            }
            catch (PDOException $e) {
                setFlash('error', 'A post type with this name or slug already exists.');
            }
        }
        else {
            setFlash('error', 'Name is required.');
        }
    }
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fas fa-file-alt');
        if ($id && $name !== '') {
            db()->prepare("UPDATE custom_post_types SET name = ?, icon = ? WHERE id = ?")->execute([$name, $icon, $id]);
            setFlash('success', 'Custom Post Type updated.');
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare("DELETE FROM custom_post_types WHERE id = ?")->execute([$id]);
            setFlash('success', 'Custom Post Type removed.');
        }
    }
    elseif ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        $validIds = array_filter(array_map('intval', (array)$ids));
        if ($validIds) {
            $placeholders = implode(',', array_fill(0, count($validIds), '?'));
            db()->prepare("DELETE FROM custom_post_types WHERE id IN ($placeholders)")->execute($validIds);
            setFlash('success', count($validIds) === 1 ? '1 post type removed.' : count($validIds) . ' post types removed.');
        }
    }

    redirect(APP_URL . '/admin/cpt-manager.php');
}

$pageTitle = 'Custom Post Types';
require_once __DIR__ . '/includes/header.php';

$cpts = db()->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM posts p
         WHERE p.post_type COLLATE utf8mb4_unicode_ci = c.slug COLLATE utf8mb4_unicode_ci
           AND p.status != 'trash') AS post_count
    FROM custom_post_types c
    ORDER BY c.name ASC
")->fetchAll();

$editCpt = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid) {
        $stmt = db()->prepare("SELECT * FROM custom_post_types WHERE id = ?");
        $stmt->execute([$eid]);
        $editCpt = $stmt->fetch() ?: null;
    }
}

$listColspan = 5;
?>
<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Custom Post Types</h2>
            <p class="text-muted">Create collections like Services, Locations, or Testimonials—same idea as WordPress
                CPTs.</p>
        </div>
    </div>

    <div class="form-row">
        <div class="form-col-4">
            <div class="form-card">
                <?php if ($editCpt): ?>
                <h3>Edit Content Type</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?=(int)$editCpt['id']?>">
                    <div class="form-group">
                        <label for="cpt-edit-name">Plural name *</label>
                        <input type="text" id="cpt-edit-name" name="name" value="<?= h($editCpt['name'])?>" required>
                    </div>
                    <div class="form-group">
                        <label for="cpt-edit-slug">Slug</label>
                        <input type="text" id="cpt-edit-slug" value="<?= h($editCpt['slug'])?>" disabled readonly
                            style="opacity:0.85;cursor:not-allowed;">
                        <small class="form-help">Slug is fixed so existing content keeps working.</small>
                    </div>
                    <div class="form-group">
                        <label for="cpt-edit-icon">Menu icon (Font Awesome)</label>
                        <input type="text" id="cpt-edit-icon" name="icon"
                            value="<?= h($editCpt['icon'] ?: 'fas fa-file-alt')?>">
                    </div>
                    <div class="category-form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Update</button>
                        <a href="<?= APP_URL?>/admin/cpt-manager.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
                <?php
else: ?>
                <h3>Add New Content Type</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label for="name">Plural name *</label>
                        <input type="text" id="name" name="name" placeholder="e.g. Services, Locations" required>
                        <small class="form-help">Shown in the admin sidebar. The URL slug is generated from this
                            name.</small>
                    </div>
                    <div class="form-group">
                        <label for="icon">Menu icon (Font Awesome)</label>
                        <input type="text" id="icon" name="icon" placeholder="fas fa-tools" value="fas fa-file-alt">
                        <small class="form-help">e.g. <code>fas fa-map-marker-alt</code></small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Create post
                        type</button>
                </form>
                <?php
endif; ?>
            </div>

            <div class="form-card mt-3">
                <h3>Custom fields</h3>
                <p class="text-muted" style="font-size: 13px;">Assign extra fields (price, address, etc.) to each post
                    type.</p>
                <a href="<?= APP_URL?>/admin/custom-fields.php" class="btn btn-outline btn-block mt-2">Manage custom
                    fields</a>
            </div>
        </div>

        <div class="form-col-8">
            <form action="" method="POST" id="cpt-bulk-form">
                <?php csrfField(); ?>
                <div class="bulk-actions-container">
                    <select name="action" id="cpt-bulk-action">
                        <option value="">Bulk actions</option>
                        <option value="bulk_delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-outline btn-sm" id="cpt-bulk-apply">Apply</button>
                </div>

                <div class="modern-card no-padding overflow-hidden">
                    <table class="modern-table category-list-table cpt-list-table">
                        <thead>
                            <tr>
                                <th class="check-column" scope="col">
                                    <input type="checkbox" id="cpt-check-all" aria-label="Select all">
                                </th>
                                <th class="column-icon" scope="col" aria-label="Icon"></th>
                                <th scope="col">Name</th>
                                <th scope="col">Slug</th>
                                <th scope="col" class="column-count">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cpts)):
    foreach ($cpts as $cpt):
        $cid = (int)$cpt['id'];
        $typeUrl = APP_URL . '/admin/posts.php?type=' . rawurlencode($cpt['slug']);
?>
                            <tr class="cpt-list-row" data-cpt-id="<?= $cid?>">
                                <th class="check-column" scope="row">
                                    <input type="checkbox" name="ids[]" value="<?= $cid?>" class="cpt-item-check"
                                        aria-label="Select <?= h($cpt['name'])?>">
                                </th>
                                <td class="column-icon"><i class="<?= h($cpt['icon'] ?: 'fas fa-file-alt')?>"
                                        aria-hidden="true"></i></td>
                                <td class="column-name">
                                    <div class="title-cell category-title-cell">
                                        <strong><a href="<?= h($typeUrl)?>" class="category-title-link"
                                                title="View all items of this type">
                                                <?= h($cpt['name'])?>
                                            </a></strong>
                                        <div class="row-actions category-row-actions cpt-row-actions">
                                            <span class="edit"><a
                                                    href="<?= APP_URL?>/admin/cpt-manager.php?edit=<?= $cid?>">Edit</a></span>
                                            <span class="row-actions-sep">|</span>
                                            <span class="quick-edit"><button type="button"
                                                    class="category-quick-edit-btn cpt-quick-edit-btn">Quick&nbsp;Edit</button></span>
                                            <span class="row-actions-sep">|</span>
                                            <span class="delete">
                                                <button type="submit" class="row-action-link-btn"
                                                    form="cpt-del-<?= $cid?>"
                                                    onclick="return confirm('Delete this custom post type? Existing posts stay in the database but won\'t show under this type.');">Delete</button>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="column-slug"><code
                                        class="category-slug-display"><?= h($cpt['slug'])?></code></td>
                                <td class="column-count">
                                    <a href="<?= h($typeUrl)?>" class="cpt-count-link" title="View all">
                                        <?=(int)$cpt['post_count']?>
                                    </a>
                                </td>
                            </tr>
                            <tr class="category-quick-edit-row cpt-quick-edit-row" id="cpt-quick-<?= $cid?>" hidden>
                                <td colspan="<?= $listColspan?>" class="category-quick-edit-cell">
                                    <div class="category-quick-edit-panel">
                                        <div class="category-quick-edit-fields">
                                            <div class="form-group category-quick-field">
                                                <label for="cpt-qe-name-<?= $cid?>">Name</label>
                                                <input type="text" id="cpt-qe-name-<?= $cid?>" name="name"
  $cid?>"
                                                 form="cpt-qe-<?= $cid ?>" value="<?= h($cpt['name'])?>" required>
                                            </div>
                                            <div class="form-group category-quick-field">
                                                <label for="cpt-qe-icon-<?= $cid?>">Icon</label>
                                                <input type="text" id="cpt-qe-icon-<?= $cid?>" name="icon"
                                                    form="cpt-qe-<?= $cid?>"
                                                    value="<?= h($cpt['icon'] ?: 'fas fa-file-alt')?>">
                                            </div>
                                        </div>
                                        <div class="category-quick-edit-buttons">
                                            <button type="submit" class="btn btn-primary btn-sm"
                                                form="cpt-qe-<?= $cid?>">Update</button>
                                            <button type="button"
                                                class="btn btn-outline btn-sm category-quick-cancel cpt-quick-cancel">Cancel</button>
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
                                        <i class="fas fa-cubes"></i>
                                        <p>No custom post types yet.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <?php if (!empty($cpts)):
    foreach ($cpts as $cpt):
        $cid = (int)$cpt['id']; ?>
            <form id="cpt-qe-<?= $cid?>" method="POST" class="category-ghost-form" aria-hidden="true">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $cid?>">
            </form>
            <form id="cpt-del-<?= $cid?>" method="POST" class="category-ghost-form" aria-hidden="true">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $cid?>">
            </form>
            <?php
    endforeach;
endif; ?>
        </div>
    </div>
</div>
<script>
    (function () {
        var bulkForm = document.getElementById('cpt-bulk-form');
        var checkAll = document.getElementById('cpt-check-all');
        if (checkAll && bulkForm) {
            checkAll.addEventListener('change', function () {
                bulkForm.querySelectorAll('.cpt-item-check').forEach(function (c) { c.checked = checkAll.checked; });
            });
        }
        if (bulkForm) {
            bulkForm.addEventListener('submit', function (e) {
                var sel = document.getElementById('cpt-bulk-action');
                if (!sel || !sel.value) {
                    e.preventDefault();
                    return;
                }
                if (sel.value !== 'bulk_delete') return;
                var any = false;
                bulkForm.querySelectorAll('.cpt-item-check').forEach(function (c) { if (c.checked) any = true; });
                if (!any) {
                    e.preventDefault();
                    return;
                }
                if (!confirm('Delete the selected post types? Posts in the database are not deleted, but types disappear from the menu.')) e.preventDefault();
            });
        }
        document.querySelectorAll('.cpt-list-row').forEach(function (row) {
            var id = row.getAttribute('data-cpt-id');
            var qeRow = document.getElementById('cpt-quick-' + id);
            if (!qeRow) return;
            var btn = row.querySelector('.cpt-quick-edit-btn');
            var cancel = qeRow.querySelector('.cpt-quick-cancel');
            function closeAll() {
                document.querySelectorAll('.cpt-quick-edit-row').forEach(function (r) { r.hidden = true; });
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