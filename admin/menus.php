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
            db()->prepare("INSERT INTO menus (name, location, structure) VALUES (?, ?, '[]')")->execute([$name, $location]);
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
        db()->prepare("DELETE FROM menus WHERE id = ?")->execute([$menuId]);
        setFlash('success', 'Menu deleted!');
        redirect("?id=0");
    }

    if ($action === 'add_item' && $menuId) {
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        if ($title && $url) {
            $structure = json_decode($menu['structure'] ?: '[]', true);
            $newItem = [
                'id' => time() . rand(10,99),
                'title' => $title,
                'url' => $url,
                'parent_id' => 0
            ];
            $structure[] = $newItem;
            db()->prepare("UPDATE menus SET structure = ? WHERE id = ?")->execute([json_encode($structure), $menuId]);
            setFlash('success', 'Item added!');
            redirect("?id=$menuId");
        }
    }

    if ($action === 'delete_item') {
        $itemId = $_POST['item_id'] ?? '';
        if ($menuId && $itemId) {
            $structure = json_decode($menu['structure'] ?: '[]', true);
            function removeFromTree(&$items, $targetId) {
                foreach ($items as $k => &$i) {
                    if ($i['id'] == $targetId) { unset($items[$k]); return true; }
                    if (isset($i['children']) && removeFromTree($i['children'], $targetId)) return true;
                }
                return false;
            }
            removeFromTree($structure, $itemId);
            db()->prepare("UPDATE menus SET structure = ? WHERE id = ?")->execute([json_encode(array_values($structure)), $menuId]);
            setFlash('success', 'Item removed!');
        }
    }

    if ($action === 'save_menu_tree' && $menuId) {
        $json = $_POST['menu_data'] ?? '[]';
        db()->prepare("UPDATE menus SET structure = ? WHERE id = ?")->execute([$json, $menuId]);
        setFlash('success', 'Menu structure saved successfully!');
    }
}

// Fetch current menu structure
$menuTree = [];
if ($menuId) {
    // Re-fetch to get latest after POST actions
    $menu = db()->prepare("SELECT * FROM menus WHERE id = ?");
    $menu->execute([$menuId]);
    $menu = $menu->fetch();
    $menuTree = json_decode($menu['structure'] ?: '[]', true);
}

$allPages = db()->query("SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY title ASC")->fetchAll();
$allCats = db()->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = 'Menus';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page" style="max-width: 1200px; margin: 0 auto;">
    <div class="admin-page-header" style="margin-bottom: 20px;">
        <h2 style="font-size: 24px; font-weight: 400; color: #1d2327;">Menus</h2>
        <div class="admin-page-header__actions">
            <select onchange="window.location.href='?id=' + this.value" class="form-control" style="width:200px; height: 32px; font-size: 13px; border-radius: 4px; border: 1px solid #c3c4c7;">
                <option value="0">Select a menu to edit</option>
                <?php foreach ($menusList as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $m['id'] == $menuId ? 'selected' : '' ?>><?= h($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="document.getElementById('create-menu-modal').style.display='flex'" class="btn btn-outline" style="height: 32px; font-weight: 600;">Create New Menu</button>
        </div>
    </div>

    <?php if ($menuId): ?>
    <div class="form-row" style="display: flex; gap: 20px;">
        <!-- Sidebar: Add Items -->
        <div style="flex: 0 0 320px;">
            <div class="form-card" style="border-radius: 8px; border: 1px solid #c3c4c7; margin-bottom: 20px;">
                <h3 style="font-size: 14px; font-weight: 600; padding: 12px 15px; border-bottom: 1px solid #f0f0f1; margin: 0;">Add Menu Items</h3>
                <div style="padding: 15px;">
                    <!-- Pages Accordion -->
                    <details id="details-pages" style="border-bottom: 1px solid #f0f0f1; margin-bottom: 10px; padding-bottom: 5px;">
                        <summary style="font-weight:600; cursor:pointer; font-size:13px; padding:8px 0; color: #1d2327; list-style: none;">
                            <i class="fas fa-caret-right" style="width: 15px; transition: 0.2s;"></i> Pages
                        </summary>
                        <div style="max-height: 250px; overflow-y: auto; padding: 10px 5px;">
                            <?php foreach ($allPages as $p): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 8px; border-bottom: 1px solid #f9f9f9;">
                                    <span style="font-size: 13px; color: #3c434a;"><?= h($p['title']) ?></span>
                                    <button onclick="addToMenu('<?= addslashes($p['title']) ?>', '/<?= $p['slug'] ?>')" class="btn btn-outline btn-sm" style="height: 26px; font-size: 11px; padding: 0 10px; font-weight: 600;">Add</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <!-- Categories Accordion -->
                    <details id="details-cats" style="border-bottom: 1px solid #f0f0f1; margin-bottom: 20px; padding-bottom: 5px;">
                        <summary style="font-weight:600; cursor:pointer; font-size:13px; padding:8px 0; color: #1d2327; list-style: none;">
                            <i class="fas fa-caret-right" style="width: 15px; transition: 0.2s;"></i> Categories
                        </summary>
                        <div style="max-height: 250px; overflow-y: auto; padding: 10px 5px;">
                            <?php foreach ($allCats as $c): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 8px; border-bottom: 1px solid #f9f9f9;">
                                    <span style="font-size: 13px; color: #3c434a;"><?= h($c['name']) ?></span>
                                    <button onclick="addToMenu('<?= addslashes($c['name']) ?>', '/category/<?= $c['slug'] ?>')" class="btn btn-outline btn-sm" style="height: 26px; font-size: 11px; padding: 0 10px; font-weight: 600;">Add</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <div style="background: #fafafa; padding: 15px; border-radius: 6px; border: 1px solid #e1e4e8;">
                        <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 600; color: #1d2327;">Custom Link</h4>
                        <form action="" method="POST" id="add-link-form">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="add_item">
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size: 11px; text-transform: uppercase; color: #646970;">URL</label>
                                <input type="text" name="url" id="add-item-url" placeholder="https://" required style="height: 32px; font-size: 13px; padding: 0 10px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="font-size: 11px; text-transform: uppercase; color: #646970;">Link Text</label>
                                <input type="text" name="title" id="add-item-title" placeholder="Menu Item" required style="height: 32px; font-size: 13px; padding: 0 10px;">
                            </div>
                            <button type="submit" class="btn btn-outline btn-block btn-sm" style="width: 100%; height: 32px; border-color: #2271b1; color: #2271b1; font-weight: 600;">Add to Menu</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="form-card" style="border-radius: 8px; border: 1px solid #c3c4c7;">
                <h3 style="font-size: 14px; font-weight: 600; padding: 12px 15px; border-bottom: 1px solid #f0f0f1; margin: 0;">Menu Settings</h3>
                <div style="padding: 15px;">
                    <form action="" method="POST">
                        <?php csrfField(); ?>
                        <input type="hidden" name="action" value="update_menu_settings">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-size: 12px;">Menu Name</label>
                            <input type="text" name="name" value="<?= h($menu['name']) ?>" required style="height: 32px; font-size: 13px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 12px;">Display Location</label>
                            <select name="location" style="height: 32px; font-size: 13px; padding: 0 8px;">
                                <option value="" <?= $menu['location'] === '' ? 'selected' : '' ?>>None</option>
                                <option value="primary" <?= $menu['location'] === 'primary' ? 'selected' : '' ?>>Header Navigation</option>
                                <option value="footer" <?= $menu['location'] === 'footer' ? 'selected' : '' ?>>Footer Links</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" style="width: 100%; height: 36px; font-weight: 600;">Save Settings</button>
                        <button type="button" onclick="deleteMenu(<?= $menuId ?>)" class="btn btn-outline btn-block btn-sm" style="width: 100%; color: #d63638; border-color: #d63638; margin-top: 10px; height: 32px;">Delete Menu</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Workspace: Menu Structure -->
        <div style="flex: 1; min-width: 0;">
            <div class="form-card" style="border-radius: 8px; border: 1px solid #c3c4c7; max-width: 800px;">
                <h3 style="font-size: 14px; font-weight: 600; padding: 12px 15px; border-bottom: 1px solid #f0f0f1; margin: 0;">Menu Structure</h3>
                <div style="padding: 20px;">
                    <p class="text-muted" style="margin-bottom: 25px; font-size: 13px;">Drag each item into the order and hierarchy you prefer. Indent items to create subgroups.</p>
                    
                    <div class="dd" id="nestable" style="max-width: 100%;">
                        <?php if (empty($menuTree)): ?>
                            <div style="padding: 40px; text-align: center; background: #fafafa; border: 1px dashed #c3c4c7; border-radius: 4px;">
                                <p style="color: #646970; font-size: 13px;">No items in this menu. Add some from the left.</p>
                            </div>
                        <?php endif; ?>
                        
                        <ol class="dd-list">
                            <?php
                            function renderMenuLevel($tree) {
                                foreach ($tree as $item) {
                                    ?>
                                    <li class="dd-item" data-id="<?= $item['id'] ?>">
                                        <div class="dd-handle-container">
                                            <div class="dd-handle"><i class="fas fa-bars"></i></div>
                                            <div class="dd-content">
                                                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                                    <div class="item-info">
                                                        <span class="item-title"><?= h($item['title']) ?></span>
                                                        <span class="item-url"><?= h($item['url']) ?></span>
                                                    </div>
                                                    <button type="button" onclick="deleteMenuItem(<?= $item['id'] ?>)" class="remove-btn"><i class="fas fa-times"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (isset($item['children']) && !empty($item['children'])): ?>
                                            <ol class="dd-list">
                                                <?php renderMenuLevel($item['children']); ?>
                                            </ol>
                                        <?php endif; ?>
                                    </li>
                                    <?php
                                }
                            }
                            renderMenuLevel($menuTree);
                            ?>
                        </ol>
                    </div>

                    <div style="margin-top: 30px; border-top: 1px solid #f0f0f1; padding-top: 20px;">
                        <form action="" method="POST" id="menu-tree-form">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="save_menu_tree">
                            <input type="hidden" name="menu_data" id="nestable-output">
                            <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 25px; font-weight: 600;">Save Menu Structure</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="form-card" style="text-align: center; padding: 80px; border-radius: 12px; border: 2px dashed #e1e4e8;">
            <i class="fas fa-list-ul" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
            <p style="color: #646970; font-size: 15px;">Please select a menu or create a new one to begin discovery.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="create-menu-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:10000; align-items:center; justify-content:center; backdrop-filter: blur(2px);">
    <div class="form-card" style="width: 400px; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 600;">Create New Menu</h3>
        <form action="" method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="create_menu">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-size: 12px;">Menu Name</label>
                <input type="text" name="name" required placeholder="Main Navigation" style="height: 36px; font-size: 14px;">
            </div>
            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; height: 38px; font-weight: 600;">Create Menu</button>
                <button type="button" onclick="document.getElementById('create-menu-modal').style.display='none'" class="btn btn-outline" style="flex: 1; height: 38px; font-weight: 600;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Nestable/2012-10-15/jquery.nestable.min.js"></script>

<script>
function addToMenu(title, url) {
    // Save accordion state before submitting
    saveAccordionState();
    document.getElementById('add-item-title').value = title;
    document.getElementById('add-item-url').value = url;
    document.getElementById('add-link-form').submit();
}

function saveAccordionState() {
    const states = {
        pages: document.getElementById('details-pages').open,
        cats: document.getElementById('details-cats').open
    };
    localStorage.setItem('menu_accordion_states', JSON.stringify(states));
}

function restoreAccordionStates() {
    const states = JSON.parse(localStorage.getItem('menu_accordion_states') || '{}');
    if (states.pages) document.getElementById('details-pages').open = true;
    if (states.cats) document.getElementById('details-cats').open = true;
}

function deleteMenuItem(id) {
    if (confirm('Remove this item?')) {
        saveAccordionState();
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

$(document).ready(function() {
    restoreAccordionStates();
    
    // Auto-save accordion state on toggle
    $('details').on('toggle', function() {
        saveAccordionState();
    });

    const $nestable = $('#nestable');
    if ($nestable.length) {
        $nestable.nestable({
            maxDepth: 3,
            callback: function(l, e) {
                const list = l.data('output') || l.nestable('serialize');
                $('#nestable-output').val(JSON.stringify(list));
            }
        });
        
        // Initial serialize
        $('#nestable-output').val(JSON.stringify($nestable.nestable('serialize')));
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
