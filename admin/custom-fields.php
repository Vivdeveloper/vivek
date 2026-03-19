<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && verifyCsrf()) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $postType = trim($_POST['post_type'] ?? '');
        $fieldLabel = trim($_POST['field_label'] ?? '');
        // auto-generate field_name slug
        $fieldName = slugify($fieldLabel);
        // underscores usually preferred for variable names
        $fieldName = str_replace('-', '_', $fieldName);
        
        $fieldType = trim($_POST['field_type'] ?? 'text');
        $options = trim($_POST['options'] ?? '');
        
        if ($postType && $fieldLabel) {
            try {
                db()->prepare("INSERT INTO custom_fields (post_type, field_name, field_label, field_type, options) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$postType, $fieldName, $fieldLabel, $fieldType, $options]);
                setFlash('success', 'Custom field created successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'A field with this name already exists for this post type.');
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare("DELETE FROM custom_fields WHERE id = ?")->execute([$id]);
            setFlash('success', 'Custom field removed.');
        }
    }
    redirect(APP_URL . '/admin/custom-fields.php');
}

$pageTitle = 'Custom Fields';
require_once __DIR__ . '/includes/header.php';

// Get all CPTs to populate dropdown
$cpts = db()->query("SELECT slug, name FROM custom_post_types ORDER BY name ASC")->fetchAll();
$cptsOptions = array_merge([['slug' => 'post', 'name' => 'Default Posts'], ['slug' => 'page', 'name' => 'Static Pages']], $cpts);

// Get all fields
$fields = db()->query("SELECT * FROM custom_fields ORDER BY post_type ASC, sort_order ASC, id DESC")->fetchAll();
?>

<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Manage Custom Fields</h2>
            <p class="text-muted">Attach extra properties (like prices, maps) to any Post Type.</p>
        </div>
        <div class="header-actions">
            <a href="<?= APP_URL ?>/admin/cpt-manager.php" class="btn btn-outline"><i class="fas fa-cubes"></i> Manage Post Types</a>
        </div>
    </div>

    <div class="form-row">
        <!-- Add Field Form -->
        <div class="form-col-4">
            <div class="form-card">
                <h3>Create a New Field</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="post_type">Attach to Post Type *</label>
                        <select id="post_type" name="post_type" required class="form-control">
                            <?php foreach($cptsOptions as $opt): ?>
                                <option value="<?= h($opt['slug']) ?>"><?= h($opt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="field_label">Field Label *</label>
                        <input type="text" id="field_label" name="field_label" placeholder="e.g. Price, Location Map URL" required>
                    </div>

                    <div class="form-group">
                        <label for="field_type">Field Type</label>
                        <select id="field_type" name="field_type" required class="form-control" onchange="toggleOptionsBox(this.value)">
                            <option value="text">Short Text</option>
                            <option value="textarea">Paragraph / Long Text</option>
                            <option value="number">Number</option>
                            <option value="image">Image Upload</option>
                            <option value="boolean">Toggle Switch (Yes/No)</option>
                            <option value="select">Dropdown Menu</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="options-group" style="display:none;">
                        <label for="options">Dropdown Options</label>
                        <textarea id="options" name="options" rows="3" placeholder="Option 1, Option 2, Option 3"></textarea>
                        <small class="form-help">Comma separated list of choices.</small>
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add Field</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Field List -->
        <div class="form-col-8">
            <div class="modern-card no-padding overflow-hidden">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Location (Post Type)</th>
                            <th>Field Label</th>
                            <th>Field Name (Code)</th>
                            <th>Type</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fields)): foreach ($fields as $field): ?>
                            <tr>
                                <td><span class="category-tag"><i class="fas fa-bullseye"></i> <?= h($field['post_type']) ?></span></td>
                                <td><strong><?= h($field['field_label']) ?></strong></td>
                                <td><code><?= h($field['field_name']) ?></code></td>
                                <td><span class="badge" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;"><?= h($field['field_type']) ?></span></td>
                                <td>
                                    <div class="row-actions">
                                        <form action="" method="POST" class="inline-form" onsubmit="return confirm('Delete this custom field? All saved data for this field will be lost permanently.')">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $field['id'] ?>">
                                            <button type="submit" class="action-btn delete" title="Delete Field"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-row-state">
                                        <i class="fas fa-sliders-h"></i>
                                        <p>No Custom Fields created.</p>
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

<script>
function toggleOptionsBox(val) {
    document.getElementById('options-group').style.display = (val === 'select') ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
