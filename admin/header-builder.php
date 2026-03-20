<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $settingsToUpdate = $_POST['settings'] ?? [];
    if (!isset($settingsToUpdate['enable_custom_header'])) {
        $settingsToUpdate['enable_custom_header'] = '0';
    }
    
    foreach ($settingsToUpdate as $key => $value) {
        updateSetting($key, $value);
    }
    setFlash('success', 'Header updated!');
    redirect(APP_URL . '/admin/header-builder.php');
}

$pageTitle = 'Header Designer Pro';
require_once __DIR__ . '/includes/header.php';

$settings = [
    'custom_header_html' => getSetting('custom_header_html', ''),
    'custom_header_css' => getSetting('custom_header_css', ''),
    'custom_header_json' => getSetting('custom_header_json', '[]'),
    'enable_custom_header' => getSetting('enable_custom_header', '0'),
    'custom_header_bg' => getSetting('custom_header_bg', '#ffffff'),
    'custom_header_sticky' => getSetting('custom_header_sticky', 'sticky'),
    'custom_header_menu_id' => getSetting('custom_header_menu_id', ''),
];

$allMenus = db()->query("SELECT * FROM menus ORDER BY name ASC")->fetchAll();
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>

<div class="admin-page">
    <div class="admin-page-header">
        <h2>Professional Header Designer</h2>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="showDefaultCode()"><i class="fas fa-code"></i> Original Code</button>
            <button type="submit" form="mainForm" class="btn btn-primary" title="Shortcut: Ctrl+S"><i class="fas fa-save"></i> Save (Ctrl+S)</button>
        </div>
    </div>

    <!-- Mode Selector -->
    <div class="settings-tabs-nav" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 1px;">
        <button type="button" class="mode-btn active" data-mode="visual" style="padding: 10px 20px; border: none; background: none; font-weight: 600; cursor: pointer;">Visual Builder</button>
        <button type="button" class="mode-btn" data-mode="code" style="padding: 10px 20px; border: none; background: none; font-weight: 600; cursor: pointer; color: #666;">Expert Editor</button>
    </div>

    <form method="POST" id="mainForm">
        <?php csrfField(); ?>
        
        <div id="visual-mode" class="mode-content">
            <div class="row header-builder-container" style="display: flex; gap: 15px;">
                
                <!-- Left Sidebar: Widgets -->
                <div class="builder-sidebar-left" style="width: 260px;">
                    <div class="form-card" style="position: sticky; top: 20px; height: calc(100vh - 150px); overflow-y: auto;">
                        <h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fas fa-plus-circle"></i> Components</h4>
                        <div id="header-components" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                            <div class="element-box" data-type="logo">
                                <i class="fas fa-image"></i>
                                <span>Logo</span>
                            </div>
                            <div class="element-box" data-type="menu-primary">
                                <i class="fas fa-bars"></i>
                                <span>Menu</span>
                            </div>
                            <div class="element-box" data-type="user-dropdown">
                                <i class="fas fa-user-circle"></i>
                                <span>User</span>
                            </div>
                            <div class="element-box" data-type="search">
                                <i class="fas fa-search"></i>
                                <span>Search</span>
                            </div>
                            <div class="element-box" data-type="cta-button">
                                <i class="fas fa-rocket"></i>
                                <span>Button</span>
                            </div>
                            <div class="element-box" data-type="social">
                                <i class="fab fa-twitter"></i>
                                <span>Social</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Center: Canvas Area -->
                <div class="builder-canvas-center" style="flex: 1;">
                    <div class="form-card" style="min-height: 500px; background: #f9f9fb; border: 2px dashed #ddd; display: flex; flex-direction: column;">
                        <h5 class="text-muted text-center mt-3">Header Design Canvas</h5>
                        
                        <!-- 3Zone Layout -->
                        <div id="header-layout-zones" style="display: grid; grid-template-columns: 1fr 2fr 1fr; background: #fff; min-height: 100px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 8px; margin: 20px; position:relative;">
                            <div class="header-zone" data-zone="left" style="border-right: 1px dashed #eee; padding: 25px 15px; display: flex; align-items: center; gap: 10px; min-width: 100px;">
                                <span class="zone-label">Left Corner</span>
                            </div>
                            <div class="header-zone" data-zone="center" style="border-right: 1px dashed #eee; padding: 25px 15px; display: flex; align-items: center; justify-content: center; gap: 10px; min-width: 100px;">
                                <span class="zone-label">Center Navigation</span>
                            </div>
                            <div class="header-zone" data-zone="right" style="padding: 25px 15px; display: flex; align-items: center; justify-content: flex-end; gap: 10px; min-width: 100px;">
                                <span class="zone-label">Right Actions</span>
                            </div>
                        </div>
                        
                        <div style="flex: 1;"></div> <!-- Spacer -->

                        <div class="mt-4 p-3" style="background: #fff; border-top: 1px solid #eee; border-radius: 0 0 8px 8px; width: 100%; overflow-x: hidden;">
                            <h5 style="margin-top:0;">Live Code Preview</h5>
                            <pre id="html-preview-hint" style="font-size:11px; color:#666; background:#f5f5f5; padding:10px; border-radius:4px; max-height:150px; overflow-y:auto; white-space: pre-wrap; word-break: break-all;"></pre>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Settings -->
                <div class="builder-sidebar-right" style="width: 260px;">
                    <div class="form-card" style="position: sticky; top: 20px;">
                        <h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fas fa-sliders-h"></i> Styling</h4>
                        <div class="form-group mt-3">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="settings[enable_custom_header]" value="1" <?= $settings['enable_custom_header'] ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                                <span style="font-weight: 600;">Enable Custom Header</span>
                            </label>
                        </div>
                        <div class="form-group mt-3">
                            <label>Navigation BG</label>
                            <input type="color" id="header-bg-pick" value="<?= h($settings['custom_header_bg']) ?>" class="form-control" style="height:40px;">
                        </div>
                        <div class="form-group mt-3">
                            <label>Sticky Header</label>
                            <select id="sticky-header" class="form-control">
                                <option value="sticky" <?= $settings['custom_header_sticky'] === 'sticky' ? 'selected' : '' ?>>Always Visible (Sticky)</option>
                                <option value="relative" <?= $settings['custom_header_sticky'] === 'relative' ? 'selected' : '' ?>>Normal Scroll</option>
                            </select>
                        </div>
                        <div class="form-group mt-3" style="border-top:1px solid #eee; padding-top:15px;">
                            <label style="color:var(--primary-color); font-weight:700;"><i class="fas fa-bars"></i> Select Active Menu</label>
                            <select name="settings[custom_header_menu_id]" id="active-menu-selector" class="form-control">
                                <option value="primary" <?= $settings['custom_header_menu_id'] === 'primary' ? 'selected' : '' ?>>Default (Primary Location)</option>
                                <?php foreach($allMenus as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $settings['custom_header_menu_id'] == $m['id'] ? 'selected' : '' ?>><?= h($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" style="display:block; margin-top:5px; font-size:11px;">The selected menu will be used in the header component.</small>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Hidden inputs -->
        <textarea name="settings[custom_header_html]" id="header_html_data" style="display:none;"><?= h($settings['custom_header_html']) ?></textarea>
        <textarea name="settings[custom_header_css]" id="header_css_data" style="display:none;"><?= h($settings['custom_header_css']) ?></textarea>
        <textarea name="settings[custom_header_json]" id="header_json_data" style="display:none;"><?= h($settings['custom_header_json']) ?></textarea>
        <input type="hidden" name="settings[custom_header_bg]" id="header_bg_data" value="<?= h($settings['custom_header_bg']) ?>">
        <input type="hidden" name="settings[custom_header_sticky]" id="header_sticky_data" value="<?= h($settings['custom_header_sticky']) ?>">

        <div id="code-mode" class="mode-content" style="display:none; padding: 0 15px;">
             <div style="display: flex; gap: 20px;">
                <div style="flex: 2;">
                    <div class="form-card" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; margin-bottom: 15px; display: block; color: var(--primary-color);">Master HTML Design (Dynamic PHP)</label>
                        <div id="ace_html_editor" style="height: 500px; border: 1px solid #ddd; border-radius: 8px;"><?= h($settings['custom_header_html']) ?></div>
                    </div>
                </div>
                <div style="flex: 1;">
                    <div class="form-card">
                        <label style="font-weight: bold; margin-bottom: 15px; display: block; color: var(--primary-color);">Master CSS</label>
                        <div id="ace_css_editor" style="height: 500px; border: 1px solid #ddd; border-radius: 8px;"><?= h($settings['custom_header_css']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.element-box { 
    padding: 15px 10px; 
    border: 1px solid #eee; 
    background: #fff; 
    border-radius: 4px; 
    cursor: grab; 
    transition: all 0.2s; 
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-align: center;
}
.element-box i { font-size: 20px; color: #555; }
.element-box span { font-size: 11px; font-weight: 500; color: #666; }
.element-box:hover { border-color: var(--primary-color); background: #f8f9ff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

.canvas-element {
    padding: 15px 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: move;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    min-width: 100px;
    position: relative;
    transition: all 0.2s;
}
.canvas-element:hover { border-color: var(--primary-color); background: #fcfdff; }
.canvas-element i.widget-icon { font-size: 18px; color: var(--primary-color); }
.canvas-element span { color: #333; }
.canvas-element .del-btn { 
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ff4d4d;
    color: #fff;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    cursor: pointer;
    border: 2px solid #fff;
}

.zone-label {
    position: absolute;
    top: -20px;
    font-size: 9px;
    text-transform: uppercase;
    color: #bbb;
    letter-spacing: 0.5px;
    font-weight: bold;
}
.header-zone { position: relative; min-height: 60px; }

.mode-btn.active { border-bottom: 3px solid var(--primary-color) !important; color: var(--primary-color) !important; }
</style>

<script>
const components = document.getElementById('header-components');
const htmlDataField = document.getElementById('header_html_data');
const cssDataField = document.getElementById('header_css_data');
const htmlPreview = document.getElementById('html-preview-hint');
const zones = document.querySelectorAll('.header-zone');

// Initialize Ace Editors using global helper
const aceHtml = setupAceEditor("ace_html_editor", "header_html_data", "html");
const aceCss = setupAceEditor("ace_css_editor", "header_css_data", "css");

const jsonDataField = document.getElementById('header_json_data');
const bgDataField = document.getElementById('header_bg_data');
const stickyDataField = document.getElementById('header_sticky_data');
const menuSelector = document.getElementById('active-menu-selector');

new Sortable(components, { 
    group: { name: 'header', pull: 'clone', put: false }, 
    sort: false, 
    animation: 150 
});

zones.forEach(zone => {
    new Sortable(zone, {
        group: 'header',
        animation: 150,
        ghostClass: 'ghost',
        onAdd: function (evt) {
            transformDroppedElement(evt.item);
            updateHTML();
        },
        onUpdate: function() { updateHTML(); }
    });
});

function transformDroppedElement(item) {
    const iconEl = item.querySelector('i');
    const spanEl = item.querySelector('span');
    if (!iconEl || !spanEl) return;
    
    const icon = iconEl.className;
    const label = spanEl.innerText;
    
    item.innerHTML = `
        <i class="${icon} widget-icon"></i> 
        <span>${label}</span> 
        <div class="del-btn" onclick="this.parentElement.remove(); updateHTML();"><i class="fas fa-times"></i></div>
    `;
    item.className = 'canvas-element';
}

// Initial Load from JSON
document.addEventListener('DOMContentLoaded', () => {
    try {
        const data = JSON.parse(jsonDataField.value || '[]');
        data.forEach(zoneData => {
            const zoneEl = document.querySelector(`.header-zone[data-zone="${zoneData.zone}"]`);
            if (zoneEl) {
                zoneData.items.forEach(itemInfo => {
                    const div = document.createElement('div');
                    div.setAttribute('data-type', itemInfo.type);
                    div.innerHTML = `<i></i><span></span>`; // Temp structure for transform
                    div.querySelector('i').className = itemInfo.icon;
                    div.querySelector('span').innerText = itemInfo.label;
                    zoneEl.appendChild(div);
                    transformDroppedElement(div);
                });
            }
        });
        updateHTML();
    } catch(e) { console.error("Load error", e); }
});

function updateHTML() {
    const bg = document.getElementById('header-bg-pick').value;
    const sticky = document.getElementById('sticky-header').value;
    const menuId = menuSelector.value;
    
    bgDataField.value = bg;
    stickyDataField.value = sticky;

    let html = `<!-- Professional Theme Builder Header -->\n<header class="luxury-nav" style="background: ${bg}; border-bottom: 1px solid #eee; position: ${sticky}; top:0; z-index:1000;">\n    <div class="container" style="display:flex; align-items:center; justify-content:space-between; padding:15px 0;">\n`;
    
    let config = [];
    
    zones.forEach(zone => {
        const zoneType = zone.getAttribute('data-zone');
        let style = "display:flex; align-items:center; gap:20px; flex:1;";
        if (zoneType === 'center') style += " justify-content:center;";
        if (zoneType === 'right') style += " justify-content:flex-end;";

        html += `        <div class="nav-section-${zoneType}" style="${style}">\n`;
        const items = Array.from(zone.children).filter(i => i.classList.contains('canvas-element'));
        
        let zoneItems = [];
        items.forEach(item => {
            const type = item.getAttribute('data-type');
            const icon = item.querySelector('i').className.replace(' widget-icon', '');
            const label = item.querySelector('span').innerText;
            zoneItems.push({ type, icon, label });
            switch(type) {
                case 'logo':
                    html += `            <a href="<?= APP_URL ?>/" style="text-decoration:none; display:flex; align-items:center; gap:10px;">\n                <i class="fas fa-chart-line" style="font-size:24px; color:var(--primary-color);"></i> \n                <span style="font-weight:bold; font-size:20px; color:#333;"><?= APP_NAME ?></span>\n            </a>\n`;
                    break;
                case 'menu-primary':
                    const menuParam = isNaN(menuId) ? `'${menuId}'` : menuId;
                    html += `            <nav style="display:flex; gap:25px;">\n                <?php $pMenu = getMenuItems(` + menuParam + `); foreach($pMenu as $m): ?>\n                    <a href="<?= $m['url'] ?>" style="text-decoration:none; color:#444; font-weight:500; font-size:14.5px;"><?= h($m['title']) ?></a>\n                <?php endforeach; ?>\n            </nav>\n`;
                    break;
                case 'user-dropdown':
                    html += `            <div class="user-action" style="font-size:14px;">\n                <?php if($_currentUser): ?>\n                    <a href="<?= APP_URL ?>/admin/" style="text-decoration:none; color:#333;"><i class="fas fa-user-circle"></i> Account</a>\n                <?php else: ?>\n                    <a href="<?= APP_URL ?>/login.php" style="text-decoration:none; color:#333;">Login</a>\n                <?php endif; ?>\n            </div>\n`;
                    break;
                case 'search':
                    html += `            <form action="<?= APP_URL ?>/search.php" style="position:relative; width:200px;">\n                <input type="text" placeholder="Search..." style="width:100%; padding:7px 15px; border-radius:20px; border:1px solid #ddd; outline:none; font-size:13px;">\n                <button style="position:absolute; right:12px; top:8px; background:none; border:none; color:#bbb;"><i class="fas fa-search"></i></button>\n            </form>\n`;
                    break;
                case 'cta-button':
                    html += `            <a href="<?= pageUrl('contact') ?>" style="padding:9px 24px; background:var(--primary-color); color:#fff; border-radius:30px; text-decoration:none; font-weight:600; font-size:13px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">Get Started</a>\n`;
                    break;
                case 'social':
                    html += `            <div class="social-links" style="display:flex; gap:15px; font-size:17px;">\n                <a href="#" style="color:#777;"><i class="fab fa-twitter"></i></a>\n                <a href="#" style="color:#777;"><i class="fab fa-instagram"></i></a>\n            </div>\n`;
                    break;
            }
        });
        html += `        </div>\n`;
        config.push({ zone: zoneType, items: zoneItems });
    });

    html += `    </div>\n</header>`;
    
    htmlDataField.value = html;
    jsonDataField.value = JSON.stringify(config);
    htmlPreview.textContent = html;
    aceHtml.setValue(html, -1);
}

document.getElementById('header-bg-pick').addEventListener('input', updateHTML);
document.getElementById('sticky-header').addEventListener('change', updateHTML);
menuSelector.addEventListener('change', updateHTML);

document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const mode = btn.getAttribute('data-mode');
        document.getElementById('visual-mode').style.display = (mode === 'visual' ? 'block' : 'none');
        document.getElementById('code-mode').style.display = (mode === 'code' ? 'block' : 'none');
        
        // Sync & Resize
        if(mode === 'code') {
            aceHtml.resize();
            aceCss.resize();
        }
    });
});

aceHtml.getSession().on('change', () => { htmlDataField.value = aceHtml.getValue(); });
aceCss.getSession().on('change', () => { cssDataField.value = aceCss.getValue(); });

// Global Save Shortcut (Ctrl+S / Cmd+S)
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('mainForm').submit();
    }
});

function showDefaultCode() {
    document.getElementById('codeModal').style.display = 'flex';
}
</script>

<div id="codeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div class="form-card" style="width:100%; max-width:800px; max-height:90vh; overflow-y:auto; position:relative;">
        <h3 style="margin-top:0;">Default Header Code</h3>
        <textarea class="form-control" rows="15" readonly style="font-family:monospace; font-size:12px; background:#f5f5f5;"><?= h(file_get_contents(__DIR__ . '/../includes/partials/header-default.php')) ?></textarea>
        <div style="margin-top:20px; text-align:right;">
            <button class="btn btn-outline" onclick="document.getElementById('codeModal').style.display='none'">Close</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
