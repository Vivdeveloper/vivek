<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $settingsToUpdate = $_POST['settings'] ?? [];
    if (!isset($settingsToUpdate['enable_custom_footer'])) {
        $settingsToUpdate['enable_custom_footer'] = '0';
    }
    
    foreach ($settingsToUpdate as $key => $value) {
        updateSetting($key, $value);
    }
    setFlash('success', 'Footer updated!');
    redirect(APP_URL . '/admin/footer-builder.php');
}

$pageTitle = 'Footer Designer Pro';
require_once __DIR__ . '/includes/header.php';

$settings = [
    'custom_footer_html' => getSetting('custom_footer_html', ''),
    'custom_footer_css' => getSetting('custom_footer_css', ''),
    'custom_footer_json' => getSetting('custom_footer_json', '[]'),
    'enable_custom_footer' => getSetting('enable_custom_footer', '0'),
    'custom_footer_bg' => getSetting('custom_footer_bg', '#1a1a2e'),
    'custom_footer_text' => getSetting('custom_footer_text', '#ffffff'),
];
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>

<div class="admin-page">
    <div class="admin-page-header">
        <h2>Professional Footer Designer</h2>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="showDefaultCode()"><i class="fas fa-code"></i> Original Code</button>
            <button type="submit" form="footerForm" class="btn btn-primary" title="Shortcut: Ctrl+S"><i class="fas fa-save"></i> Save (Ctrl+S)</button>
        </div>
    </div>

    <!-- Mode Selector -->
    <div class="settings-tabs-nav" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 1px;">
        <button type="button" class="mode-btn active" data-mode="visual" style="padding: 10px 20px; border: none; background: none; font-weight: 600; cursor: pointer;">Visual Builder</button>
        <button type="button" class="mode-btn" data-mode="code" style="padding: 10px 20px; border: none; background: none; font-weight: 600; cursor: pointer; color: #666;">Expert Editor</button>
    </div>

    <form method="POST" id="footerForm">
        <?php csrfField(); ?>
        
        <div id="visual-mode" class="mode-content">
            <div class="row builder-container" style="display: flex; gap: 15px;">
                
                <!-- Left Sidebar: Widgets -->
                <div class="builder-sidebar-left" style="width: 260px;">
                    <div class="form-card" style="position: sticky; top: 20px; height: calc(100vh - 150px); overflow-y: auto;">
                        <h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fas fa-plus-circle"></i> Components</h4>
                        <div id="footer-components" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                            <div class="element-box" data-type="about">
                                <i class="fas fa-info-circle"></i>
                                <span>About Us</span>
                            </div>
                            <div class="element-box" data-type="menu-footer">
                                <i class="fas fa-bars"></i>
                                <span>Quick Links</span>
                            </div>
                            <div class="element-box" data-type="contact-info">
                                <i class="fas fa-id-card"></i>
                                <span>Contact</span>
                            </div>
                            <div class="element-box" data-type="newsletter">
                                <i class="fas fa-paper-plane"></i>
                                <span>Newsletter</span>
                            </div>
                            <div class="element-box" data-type="social-icons">
                                <i class="fab fa-instagram"></i>
                                <span>Social</span>
                            </div>
                            <div class="element-box" data-type="copyright">
                                <i class="fas fa-copyright"></i>
                                <span>Copyright</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Center: Canvas Area -->
                <div class="builder-canvas-center" style="flex: 1;">
                    <div class="form-card" style="min-height: 500px; background: #f9f9fb; border: 2px dashed #ddd; display: flex; flex-direction: column;">
                        <h5 class="text-muted text-center mt-3">Footer Architecture Canvas</h5>
                        
                        <!-- 4Column Grid Layout -->
                        <div class="row" style="padding: 20px; gap: 15px;">
                            <?php for($i=1; $i<=4; $i++): ?>
                            <div class="col footer-zone" data-zone="<?= $i ?>" style="min-height: 250px; background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 25px 15px; position:relative; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                                <span class="zone-label" style="position:absolute; top:-10px; left:50%; transform:translateX(-50%); font-size:9px; color:#bbb; text-transform:uppercase; letter-spacing:1px; font-weight:bold; background:#fff; padding:2px 8px;">Column <?= $i ?></span>
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div style="flex: 1;"></div> <!-- Spacer -->

                        <div class="p-3" style="background: #fff; border-top: 1px solid #eee; border-radius: 0 0 8px 8px; width: 100%; overflow-x: hidden;">
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
                                <input type="checkbox" name="settings[enable_custom_footer]" value="1" <?= $settings['enable_custom_footer'] ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                                <span style="font-weight: 600;">Enable Footer</span>
                            </label>
                        </div>
                        <div class="form-group mt-3">
                            <label>Background</label>
                            <input type="color" id="footer-bg-pick" value="<?= h($settings['custom_footer_bg']) ?>" class="form-control" style="height:40px;">
                        </div>
                        <div class="form-group mt-3">
                            <label>Text Color</label>
                            <input type="color" id="footer-text-pick" value="<?= h($settings['custom_footer_text']) ?>" class="form-control" style="height:40px;">
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Hidden inputs -->
        <textarea name="settings[custom_footer_html]" id="footer_html_data" style="display:none;"><?= h($settings['custom_footer_html']) ?></textarea>
        <textarea name="settings[custom_footer_css]" id="footer_css_data" style="display:none;"><?= h($settings['custom_footer_css']) ?></textarea>
        <textarea name="settings[custom_footer_json]" id="footer_json_data" style="display:none;"><?= h($settings['custom_footer_json']) ?></textarea>
        <input type="hidden" name="settings[custom_footer_bg]" id="footer_bg_data" value="<?= h($settings['custom_footer_bg']) ?>">
        <input type="hidden" name="settings[custom_footer_text]" id="footer_text_data" value="<?= h($settings['custom_footer_text']) ?>">

        <div id="code-mode" class="mode-content" style="display:none; padding: 0 15px;">
             <div style="display: flex; gap: 20px;">
                <div style="flex: 2;">
                    <div class="form-card" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; margin-bottom: 15px; display: block; color: var(--primary-color);">Master Footer HTML (Dynamic PHP)</label>
                        <div id="ace_html_editor" style="height: 500px; border: 1px solid #ddd; border-radius: 8px;"><?= h($settings['custom_footer_html']) ?></div>
                    </div>
                </div>
                <div style="flex: 1;">
                    <div class="form-card">
                        <label style="font-weight: bold; margin-bottom: 15px; display: block; color: var(--primary-color);">Master CSS</label>
                        <div id="ace_css_editor" style="height: 500px; border: 1px solid #ddd; border-radius: 8px;"><?= h($settings['custom_footer_css']) ?></div>
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
    padding: 15px 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 15px;
    cursor: move;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    position: relative;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    width: 100%;
}
.canvas-element .del-btn { 
    position: absolute; top: -8px; right: -8px; 
    background: #ff4d4d; color: #fff; width: 22px; height: 22px; 
    border-radius: 50%; display: flex; align-items: center; justify-content: center; 
    font-size: 11px; cursor: pointer; border: 2px solid #fff;
}
.canvas-element i.widget-icon { font-size: 20px; color: var(--primary-color); }
.mode-btn.active { border-bottom: 3px solid var(--primary-color) !important; color: var(--primary-color) !important; }
</style>

<script>
const components = document.getElementById('footer-components');
const htmlDataField = document.getElementById('footer_html_data');
const jsonDataField = document.getElementById('footer_json_data');
const htmlPreviewHint = document.getElementById('html-preview-hint');
const zones = document.querySelectorAll('.footer-zone');

// Initialize Ace Editors using global helper
const aceHtml = setupAceEditor("ace_html_editor", "footer_html_data", "html");
const aceCss = setupAceEditor("ace_css_editor", "footer_css_data", "css");

new Sortable(components, { 
    group: { name: 'footer', pull: 'clone', put: false }, 
    sort: false, 
    animation: 150 
});

zones.forEach(zone => {
    new Sortable(zone, {
        group: 'footer',
        animation: 150,
        onAdd: function (evt) {
            transformDroppedElement(evt.item);
            updateFooterHTML();
        },
        onUpdate: function() { updateFooterHTML(); }
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
        <div class="del-btn" onclick="this.parentElement.remove(); updateFooterHTML();"><i class="fas fa-times"></i></div>
    `;
    item.className = 'canvas-element';
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        const data = JSON.parse(jsonDataField.value || '[]');
        data.forEach(zoneData => {
            const zoneEl = document.querySelector(`.footer-zone[data-zone="${zoneData.zone}"]`);
            if (zoneEl) {
                zoneData.items.forEach(itemInfo => {
                    const div = document.createElement('div');
                    div.setAttribute('data-type', itemInfo.type);
                    div.innerHTML = `<i></i><span></span>`;
                    div.querySelector('i').className = itemInfo.icon;
                    div.querySelector('span').innerText = itemInfo.label;
                    zoneEl.appendChild(div);
                    transformDroppedElement(div);
                });
            }
        });
        updateFooterHTML();
    } catch(e) { console.error("Load failed", e); }
});

function updateFooterHTML() {
    const bg = document.getElementById('footer-bg-pick').value;
    const text = document.getElementById('footer-text-pick').value;
    
    document.getElementById('footer_bg_data').value = bg;
    document.getElementById('footer_text_data').value = text;

    let html = `<!-- Visual Footer Designer Generated -->\n<footer class="custom-premium-footer" style="background: ${bg}; color: ${text}; padding: 60px 0 20px;">\n    <div class="container" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">\n`;
    
    let config = [];
    zones.forEach(zone => {
        const colId = zone.getAttribute('data-zone');
        html += `        <div class="footer-col">\n`;
        const items = Array.from(zone.children).filter(i => i.classList.contains('canvas-element'));
        
        let zoneItems = [];
        items.forEach(item => {
            const type = item.getAttribute('data-type');
            const icon = item.querySelector('i').className.replace(' widget-icon', '');
            const label = item.querySelector('span').innerText;
            zoneItems.push({ type, icon, label });

            switch(type) {
                case 'about':
                    html += `            <div style="margin-bottom:25px;">\n                <h3><i class="fas fa-chart-line"></i> <?= APP_NAME ?></h3>\n                <p style="opacity:0.8; font-size:14px; margin-top:10px;"><?= h(getSetting('footer_desc')) ?></p>\n            </div>\n`;
                    break;
                case 'menu-footer':
                    html += `            <div style="margin-bottom:25px;">\n                <h4 style="margin-bottom:15px;">Links</h4>\n                <ul style="list-style:none; padding:0;">\n                    <?php $fMenu = getMenuItems('footer'); foreach($fMenu as $m): ?>\n                        <li style="margin-bottom:8px;"><a href="<?= $m['url'] ?>" style="color:inherit; text-decoration:none; opacity:0.7;"><?= h($m['title']) ?></a></li>\n                    <?php endforeach; ?>\n                </ul>\n            </div>\n`;
                    break;
                case 'contact-info':
                    html += `            <div style="margin-bottom:25px;">\n                <h4 style="margin-bottom:15px;">Contact</h4>\n                <p style="opacity:0.8; font-size:14px;"><i class="fas fa-envelope"></i> <?= h(getSetting('footer_email')) ?></p>\n                <p style="opacity:0.8; font-size:14px;"><i class="fas fa-phone-alt"></i> <?= h(getSetting('footer_phone')) ?></p>\n            </div>\n`;
                    break;
                case 'newsletter':
                    html += `            <div style="margin-bottom:25px;">\n                <h4 style="margin-bottom:15px;">Newsletter</h4>\n                <form style="display:flex; gap:5px;">\n                    <input type="email" placeholder="Your Email" style="flex:1; padding:8px; border-radius:4px; border:none;">\n                    <button style="background:var(--primary-color); color:#fff; border:none; padding:8px 15px; border-radius:4px;"><i class="fas fa-arrow-right"></i></button>\n                </form>\n            </div>\n`;
                    break;
                case 'social-icons':
                    html += `            <div style="display:flex; gap:15px;">\n                <a href="#" style="color:inherit; font-size:18px;"><i class="fab fa-twitter"></i></a>\n                <a href="#" style="color:inherit; font-size:18px;"><i class="fab fa-linkedin-in"></i></a>\n            </div>\n`;
                    break;
                case 'copyright':
                    html += `            <p style="opacity:0.5; font-size:12px; margin-top:10px;">&copy; <?= date('Y') ?> <?= APP_NAME ?>.</p>\n`;
                    break;
            }
        });
        html += `        </div>\n`;
        config.push({ zone: colId, items: zoneItems });
    });

    html += `    </div>\n</footer>`;
    htmlDataField.value = html;
    jsonDataField.value = JSON.stringify(config);
    htmlPreviewHint.textContent = html;
    aceHtml.setValue(html, -1);
}

document.getElementById('footer-bg-pick').addEventListener('input', updateFooterHTML);
document.getElementById('footer-text-pick').addEventListener('input', updateFooterHTML);

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

// Shortcut Save
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('footerForm').submit();
    }
});

function showDefaultCode() { document.getElementById('codeModal').style.display = 'flex'; }
</script>

<div id="codeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div class="form-card" style="width:100%; max-width:800px; max-height:90vh; overflow-y:auto; position:relative;">
        <h3 style="margin-top:0;">Default Footer Code</h3>
        <textarea class="form-control" rows="15" readonly style="font-family:monospace; font-size:12px; background:#f5f5f5;"><?= h(file_get_contents(__DIR__ . '/../includes/partials/footer-default.php')) ?></textarea>
        <div style="margin-top:20px; text-align:right;">
            <button class="btn btn-outline" onclick="document.getElementById('codeModal').style.display='none'">Close</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
