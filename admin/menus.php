<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

$menuId = intval($_GET['id'] ?? 0);
if (!$menuId) {
    $menu = db()->query("SELECT * FROM menus LIMIT 1")->fetch();
    $menuId = $menu ? $menu['id'] : 0;
} else {
    $menu = db()->prepare("SELECT * FROM menus WHERE id = ?");
    $menu->execute([$menuId]);
    $menu = $menu->fetch();
}

$menusList = db()->query("SELECT * FROM menus ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_menu') {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        if ($name) {
            db()->prepare("INSERT INTO menus (name, location) VALUES (?, ?)")->execute([$name, $location]);
            $newId = db()->lastInsertId();
            setFlash('success', 'New menu created!');
            redirect("?id=$newId");
        }
    }

    if ($action === 'update_menu_settings' && $menuId) {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        if ($name) {
            db()->prepare("UPDATE menus SET name = ?, location = ? WHERE id = ?")->execute([$name, $location, $menuId]);
            setFlash('success', 'Menu settings updated!');
        }
    }

    if ($action === 'delete_menu' && $menuId) {
        db()->prepare("DELETE FROM menu_items WHERE menu_id = ?")->execute([$menuId]);
        db()->prepare("DELETE FROM menus WHERE id = ?")->execute([$menuId]);
        setFlash('success', 'Menu deleted!');
        redirect("?id=0");
    }

    if ($action === 'add_item' && $menuId) {
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        if ($title && $url) {
            db()->prepare("INSERT INTO menu_items (menu_id, title, url, order_index) SELECT ?, ?, ?, COALESCE(MAX(order_index),0)+1 FROM menu_items WHERE menu_id = ?")
                ->execute([$menuId, $title, $url, $menuId]);
            setFlash('success', 'Item added!');
        }
    }

    if ($action === 'delete_item') {
        $itemId = intval($_POST['item_id'] ?? 0);
        db()->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$itemId]);
        setFlash('success', 'Item removed!');
    }

    if ($action === 'save_menu') {
        $itemsArr = $_POST['items'] ?? [];
        foreach ($itemsArr as $id => $data) {
            db()->prepare("UPDATE menu_items SET title = ?, url = ?, order_index = ? WHERE id = ?")
                ->execute([$data['title'], $data['url'], $data['order'], $id]);
        }
        setFlash('success', 'Menu order saved!');
    }
}

$items = [];
if ($menuId) {
    $stmt = db()->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY order_index ASC");
    $stmt->execute([$menuId]);
    $items = $stmt->fetchAll();
}

$allPages = db()->query("SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY title ASC")->fetchAll();
$allCats = db()->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = 'Menus';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h2>Menus</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <select onchange="window.location.href='?id=' + this.value" class="form-control" style="width:200px;">
                <option value="0">Select a menu to edit</option>
                <?php foreach ($menusList as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $m['id'] == $menuId ? 'selected' : '' ?>><?= h($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="document.getElementById('create-menu-modal').style.display='flex'" class="btn btn-outline">Create New Menu</button>
        </div>
    </div>

    <?php if ($menuId): ?>
    <div class="form-row">
        <!-- Sidebar: Add Items -->
        <div class="form-col-4">
            <div class="form-card">
                <h3>Add Menu Items</h3>
                
                <!-- Pages Accordion -->
                <details style="border-bottom: 1px solid #eee; margin-bottom: 10px; padding-bottom: 10px;">
                    <summary style="font-weight:600; cursor:pointer; font-size:13px; padding:5px 0;">Pages <span style="float:right; opacity:0.5;">▼</span></summary>
                    <div style="max-height: 200px; overflow-y: auto; padding: 10px 0;">
                        <?php foreach ($allPages as $p): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <span style="font-size: 13px;"><?= h($p['title']) ?></span>
                                <button onclick="addToMenu('<?= addslashes($p['title']) ?>', '/<?= $p['slug'] ?>')" class="btn btn-outline btn-sm">Add</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- Categories Accordion -->
                <details style="border-bottom: 1px solid #eee; margin-bottom: 15px; padding-bottom: 10px;">
                    <summary style="font-weight:600; cursor:pointer; font-size:13px; padding:5px 0;">Categories <span style="float:right; opacity:0.5;">▼</span></summary>
                    <div style="max-height: 200px; overflow-y: auto; padding: 10px 0;">
                        <?php foreach ($allCats as $c): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <span style="font-size: 13px;"><?= h($c['name']) ?></span>
                                <button onclick="addToMenu('<?= addslashes($c['name']) ?>', '/category/<?= $c['slug'] ?>')" class="btn btn-outline btn-sm">Add</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- Custom Link -->
                <div style="border-top: 2px solid #f0f0f1; padding-top: 15px;">
                    <h4 style="margin: 0 0 10px; font-size: 13px; font-weight: 600;">Custom Link</h4>
                    <form action="" method="POST" id="add-link-form">
                        <?php csrfField(); ?>
                        <input type="hidden" name="action" value="add_item">
                        <div class="form-group"><label>URL</label><input type="text" name="url" id="add-item-url" placeholder="https://" required></div>
                        <div class="form-group"><label>Link Text</label><input type="text" name="title" id="add-item-title" placeholder="Menu Item" required></div>
                        <button type="submit" class="btn btn-outline btn-block btn-sm">Add to Menu</button>
                    </form>
                </div>
            </div>

            <div class="form-card">
                <h3>Menu Settings</h3>
                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="update_menu_settings">
                    <div class="form-group"><label>Menu Name</label><input type="text" name="name" value="<?= h($menu['name']) ?>" required></div>
                    <div class="form-group">
                        <label>Display Location</label>
                        <select name="location">
                            <option value="" <?= $menu['location'] === '' ? 'selected' : '' ?>>None</option>
                            <option value="primary" <?= $menu['location'] === 'primary' ? 'selected' : '' ?>>Header Navigation</option>
                            <option value="footer" <?= $menu['location'] === 'footer' ? 'selected' : '' ?>>Footer Links</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save Menu Settings</button>
                    <button type="button" onclick="deleteMenu(<?= $menuId ?>)" class="btn btn-outline btn-block btn-sm" style="color: #d63638; border-color: #d63638; margin-top: 10px;">Delete Menu</button>
                </form>
            </div>
        </div>

        <!-- Main Workspace: Menu Structure -->
        <div class="form-col-8">
            <div class="form-card">
                <h3>Menu Structure</h3>
                <p class="text-muted" style="margin-bottom: 20px;">Drag each item into the order you prefer.</p>
                
                <form action="" method="POST" id="menu-save-form">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="save_menu">
                    
                    <div id="menu-items-list">
                        <?php if (empty($items)): ?>
                            <div style="padding: 40px; text-align: center; background: #fafafa; border: 1px dashed #c3c4c7;">
                                <p>No items in this menu. Add some from the left.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($items as $index => $item): ?>
                            <div class="menu-item-item" style="border: 1px solid #c3c4c7; background: #fdfdfd; padding: 10px 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 15px;">
                                <i class="fas fa-bars" style="cursor: move; color: #888;"></i>
                                <div style="flex: 1; display:flex; gap:10px;">
                                    <input type="text" name="items[<?= $item['id'] ?>][title]" value="<?= h($item['title']) ?>" style="background:transparent; border:none; font-weight:600; width: 40%;">
                                    <input type="text" name="items[<?= $item['id'] ?>][url]" value="<?= h($item['url']) ?>" style="background:transparent; border:none; color: #666; width: 60%; font-size: 12px;">
                                </div>
                                <input type="hidden" name="items[<?= $item['id'] ?>][order]" class="order-val" value="<?= $item['order_index'] ?>">
                                <button type="button" onclick="deleteMenuItem(<?= $item['id'] ?>)" style="background:none; border:none; color:#d63638; cursor:pointer;" title="Remove"><i class="fas fa-times"></i></button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Save Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="form-card" style="text-align: center; padding: 60px;">
            <p>Please select a menu or create a new one.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="create-menu-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
    <div class="form-card" style="width: 400px; padding: 30px;">
        <h3>Create New Menu</h3>
        <form action="" method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="create_menu">
            <div class="form-group"><label>Menu Name</label><input type="text" name="name" required placeholder="Header Menu"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Create</button>
                <button type="button" onclick="document.getElementById('create-menu-modal').style.display='none'" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function addToMenu(title, url) {
    document.getElementById('add-item-title').value = title;
    document.getElementById('add-item-url').value = url;
    document.getElementById('add-link-form').submit();
}
function deleteMenuItem(id) {
    if (confirm('Remove this item?')) {
        const f = document.createElement('form'); f.method = 'POST';
        f.innerHTML = '<?php csrfField(); ?><input type="hidden" name="action" value="delete_item"><input type="hidden" name="item_id" value="'+id+'">';
        document.body.appendChild(f); f.submit();
    }
}
function deleteMenu(id) {
    if (confirm('Delete the entire menu?')) {
        const f = document.createElement('form'); f.method = 'POST';
        f.innerHTML = '<?php csrfField(); ?><input type="hidden" name="action" value="delete_menu">';
        document.body.appendChild(f); f.submit();
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('menu-items-list');
    if (list) {
        new Sortable(list, {
            animation: 150, handle: '.fa-bars', ghostClass: 'sortable-ghost',
            onUpdate: () => {
                const rows = list.querySelectorAll('.menu-item-item');
                rows.forEach((row, idx) => { row.querySelector('.order-val').value = idx + 1; });
            }
        });
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
