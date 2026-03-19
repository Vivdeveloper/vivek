<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();
requireAdmin(); // Only admin should mess with Custom Post Types

// Handle CRUD operations for CPTs
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
            } catch (PDOException $e) {
                setFlash('error', 'A post type with this name already exists.');
            }
        } else {
            setFlash('error', 'Name is required.');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            // we should not delete the actual posts connected to it initially to strictly avoid data loss, 
            // but we will delete the CPT definition.
            db()->prepare("DELETE FROM custom_post_types WHERE id = ?")->execute([$id]);
            setFlash('success', 'Custom Post Type removed.');
        }
    }
    
    redirect(APP_URL . '/admin/cpt-manager.php');
}

$pageTitle = 'Custom Post Types';
require_once __DIR__ . '/includes/header.php';

$cpts = db()->query("SELECT * FROM custom_post_types ORDER BY name ASC")->fetchAll();
?>

<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Custom Post Types</h2>
            <p class="text-muted">Create Custom Collections like Services, Locations, Testimonials.</p>
        </div>
    </div>

    <div class="form-row">
        <!-- Add CPT Form -->
        <div class="form-col-4">
            <div class="form-card">
                <h3>Add New Content Type</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="name">Plural Name *</label>
                        <input type="text" id="name" name="name" placeholder="e.g. Services, Locations" required>
                        <small class="form-help">The name that will appear in the sidebar.</small>
                    </div>

                    <div class="form-group">
                        <label for="icon">Menu Icon (FontAwesome)</label>
                        <input type="text" id="icon" name="icon" placeholder="fas fa-tools" value="fas fa-file-alt">
                        <small class="form-help">Find icons at fontawesome.com (e.g., fas fa-map-marker-alt).</small>
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Create Post Type</button>
                    </div>
                </form>
            </div>
            
            <div class="form-card mt-3">
                <h3>Setup Custom Fields</h3>
                <p class="text-muted" style="font-size: 13px;">After creating a Post Type here, you can assign special custom fields (Prices, Address, Checkboxes) to it.</p>
                <a href="<?= APP_URL ?>/admin/custom-fields.php" class="btn btn-outline btn-block mt-2">Manage Custom Fields</a>
            </div>
        </div>

        <!-- CPT List -->
        <div class="form-col-8">
            <div class="modern-card no-padding overflow-hidden">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="50">Icon</th>
                            <th>Post Type Name</th>
                            <th>Slug (Database)</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cpts)): foreach ($cpts as $cpt): ?>
                            <tr>
                                <td class="text-center"><i class="<?= h($cpt['icon']) ?>" style="font-size:18px; color:var(--text-secondary);"></i></td>
                                <td>
                                    <div class="title-cell">
                                        <strong><?= h($cpt['name']) ?></strong>
                                    </div>
                                </td>
                                <td><span class="category-tag"><?= h($cpt['slug']) ?></span></td>
                                <td>
                                    <div class="row-actions">
                                        <form action="" method="POST" class="inline-form" onsubmit="return confirm('Delete this custom post type? Existing posts will not be deleted but they will become effectively hidden.')">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cpt['id'] ?>">
                                            <button type="submit" class="action-btn delete" title="Delete CPT"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-row-state">
                                        <i class="fas fa-cubes"></i>
                                        <p>No Custom Post Types yet.</p>
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
