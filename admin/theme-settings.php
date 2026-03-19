<?php
/**
 * Theme Settings - Custom Design Management
 */
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdmin();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $settingsToSave = $_POST['settings'] ?? [];

    // Checkboxes are not sent via POST when unchecked, so we default them to '0'
    $checkboxes = ['enable_custom_header', 'enable_custom_footer'];
    foreach ($checkboxes as $cb) {
        if (!isset($settingsToSave[$cb])) {
            $settingsToSave[$cb] = '0';
        }
    }

    foreach ($settingsToSave as $key => $value) {
        updateSetting($key, $value);
    }
    setFlash('success', 'Theme settings updated successfully!');
    redirect(APP_URL . '/admin/theme-settings.php' . ($_GET['tab'] ? '?tab='.$_GET['tab'] : ''));
}

$pageTitle = 'Design Settings';
require_once __DIR__ . '/includes/header.php';

// Get current values
$settings = [
    'footer_desc' => getSetting('footer_desc', "We're a full-service SEO and web design agency helping businesses dominate search results and grow online. Custom strategies, transparent reporting, real results."),
    'footer_email' => getSetting('footer_email', 'contact@seowebsitedesigner.com'),
    'footer_phone' => getSetting('footer_phone', '+91 123 456 7890'),
    'footer_address' => getSetting('footer_address', 'Mumbai, India'),
    
    'custom_header_html' => getSetting('custom_header_html', ''),
    'custom_header_css' => getSetting('custom_header_css', ''),
    'enable_custom_header' => getSetting('enable_custom_header', '0'),
    
    'custom_footer_html' => getSetting('custom_footer_html', ''),
    'custom_footer_css' => getSetting('custom_footer_css', ''),
    'enable_custom_footer' => getSetting('enable_custom_footer', '0'),
    
    'custom_css' => getSetting('custom_css', ''),
    'header_scripts' => getSetting('header_scripts', ''),
    'footer_scripts' => getSetting('footer_scripts', ''),
    
    'mobile_menu_bg' => getSetting('mobile_menu_bg', '#ffffff'),
    'mobile_menu_text' => getSetting('mobile_menu_text', '#333333'),

    'permalink_structure' => getSetting('permalink_structure', 'post_name'),
    'front_page_id' => getSetting('front_page_id', '0'),
];
$allPagesList = db()->query("SELECT id, title FROM pages ORDER BY title")->fetchAll();

$activeTab = $_GET['tab'] ?? 'header';
?>

<div class="row">
    <div class="col-md-12">
        <form method="POST" class="admin-form">
            <?php csrfField(); ?>
            <div class="admin-page-header">
                <div class="header-left">
                    <h2>Design & Theme Customizer</h2>
                    <p class="text-muted">Manage your site's global header, footer, and homepage aesthetics.</p>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save All Changes</button>
            </div>
            
            <div class="form-card" style="padding:0; overflow:hidden;">
                <!-- Internal Tabs Like Screenshot -->
                <div class="filter-tabs-container" style="padding: 10px 20px 0; background: #f6f7f7; border-bottom: 1px solid #c3c4c7;">
                    <div class="filter-tabs" id="design-tabs">
                        <div class="filter-tab <?= ($activeTab === 'header') ? 'active' : '' ?>" data-target="header">Header</div>
                        <div class="filter-tab <?= ($activeTab === 'footer') ? 'active' : '' ?>" data-target="footer">Footer</div>
                        <div class="filter-tab <?= ($activeTab === 'homepage') ? 'active' : '' ?>" data-target="homepage">Homepage</div>
                        <div class="filter-tab <?= ($activeTab === 'customcode') ? 'active' : '' ?>" data-target="customcode">Custom Code</div>
                    </div>
                </div>

                <div style="padding: 25px;">
                    <!-- HEADER TAB -->
                    <div id="tab-header" class="tab-content <?= $activeTab === 'header' ? 'active' : '' ?>">
                        <div class="settings-group">
                            <h3><i class="fas fa-window-maximize"></i> Global Header Injection</h3>
                            <p class="text-muted">You can override the default header with custom HTML/CSS here.</p>
                            
                            <div class="form-group" style="margin: 20px 0;">
                                <label class="checkbox-label" style="display:flex; align-items:center; gap:10px;">
                                    <input type="checkbox" name="settings[enable_custom_header]" value="1" <?= $settings['enable_custom_header'] ? 'checked' : '' ?>>
                                    Enable Custom Header Design
                                </label>
                            </div>

                            <div class="form-row">
                                <div class="form-col-8">
                                    <div class="form-group">
                                        <label>Header HTML</label>
                                        <input type="hidden" name="settings[custom_header_html]" id="header_html_field" value="<?= h($settings['custom_header_html']) ?>">
                                        <div id="header_html_editor" style="border:1px solid #ddd; border-radius:4px;"><?= h($settings['custom_header_html']) ?></div>
                                    </div>
                                </div>
                                <div class="form-col-4">
                                    <div class="form-group">
                                        <label>Header CSS</label>
                                        <input type="hidden" name="settings[custom_header_css]" id="header_css_field" value="<?= h($settings['custom_header_css']) ?>">
                                        <div id="header_css_editor" style="border:1px solid #ddd; border-radius:4px;"><?= h($settings['custom_header_css']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER TAB -->
                    <div id="tab-footer" class="tab-content <?= $activeTab === 'footer' ? 'active' : '' ?>">
                        <div class="settings-group">
                            <h3><i class="fas fa-window-minimize"></i> Global Footer Settings</h3>
                            <div class="form-row">
                                <div class="form-col-8">
                                    <div class="form-group">
                                        <label>Footer Description</label>
                                        <textarea name="settings[footer_desc]" class="form-control" rows="3"><?= h($settings['footer_desc']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Company Address</label>
                                        <input type="text" name="settings[footer_address]" value="<?= h($settings['footer_address']) ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col-4">
                                    <div class="form-group">
                                        <label>Contact Email</label>
                                        <input type="email" name="settings[footer_email]" value="<?= h($settings['footer_email']) ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Contact Phone</label>
                                        <input type="text" name="settings[footer_phone]" value="<?= h($settings['footer_phone']) ?>" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">
                            
                            <h4>Custom Footer Injection</h4>
                            <div class="form-group" style="margin: 15px 0;">
                                <label class="checkbox-label" style="display:flex; align-items:center; gap:10px;">
                                    <input type="checkbox" name="settings[enable_custom_footer]" value="1" <?= $settings['enable_custom_footer'] ? 'checked' : '' ?>>
                                    Enable Custom Footer Design
                                </label>
                            </div>
                            <div class="form-row">
                                <div class="form-col-8">
                                    <div class="form-group">
                                        <label>Footer HTML</label>
                                        <input type="hidden" name="settings[custom_footer_html]" id="footer_html_field" value="<?= h($settings['custom_footer_html']) ?>">
                                        <div id="footer_html_editor" style="border:1px solid #ddd; border-radius:4px;"><?= h($settings['custom_footer_html']) ?></div>
                                    </div>
                                </div>
                                <div class="form-col-4">
                                    <div class="form-group">
                                        <label>Footer CSS</label>
                                        <input type="hidden" name="settings[custom_footer_css]" id="footer_css_field" value="<?= h($settings['custom_footer_css']) ?>">
                                        <div id="footer_css_editor" style="border:1px solid #ddd; border-radius:4px;"><?= h($settings['custom_footer_css']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HOMEPAGE TAB -->
                    <div id="tab-homepage" class="tab-content <?= $activeTab === 'homepage' ? 'active' : '' ?>">
                        <div class="settings-group">
                            <h3><i class="fas fa-home"></i> Frontend Navigation Logic</h3>
                            
                            <div class="form-group">
                                <label>Your homepage displays</label>
                                <select name="settings[front_page_id]" class="form-control" style="width: 300px;">
                                    <option value="0">--- Select Home Page ---</option>
                                    <?php foreach ($allPagesList as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $settings['front_page_id'] == $p['id'] ? 'selected' : '' ?>><?= h($p['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

                            <h4>Permalink Structure</h4>
                            <div class="form-group" style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                                <label style="display:flex; align-items:center; gap:10px;"><input type="radio" name="settings[permalink_structure]" value="plain" <?= $settings['permalink_structure'] === 'plain' ? 'checked' : '' ?>> Plain (/?p=123)</label>
                                <label style="display:flex; align-items:center; gap:10px;"><input type="radio" name="settings[permalink_structure]" value="post_name" <?= $settings['permalink_structure'] === 'post_name' ? 'checked' : '' ?>> Post Name (/sample-post)</label>
                                <label style="display:flex; align-items:center; gap:10px;"><input type="radio" name="settings[permalink_structure]" value="category_post_name" <?= $settings['permalink_structure'] === 'category_post_name' ? 'checked' : '' ?>> Category & Post Name (/category/sample-post)</label>
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOM CODE TAB -->
                    <div id="tab-customcode" class="tab-content <?= $activeTab === 'customcode' ? 'active' : '' ?>">
                        <div class="settings-group">
                            <h3><i class="fas fa-brackets-curly"></i> Advanced Code Injection</h3>
                            
                            <div class="form-row">
                                <div class="form-col-12" style="margin-top:10px;">
                                    <div class="form-group">
                                        <label>Header Scripts (&lt;/head&gt;)</label>
                                        <input type="hidden" name="settings[header_scripts]" id="header_scripts_field" value="<?= h($settings['header_scripts']) ?>">
                                        <div id="header_scripts_editor" style="border:1px solid #ddd; border-radius:4px; min-height: 200px;"><?= h($settings['header_scripts']) ?></div>
                                    </div>
                                </div>
                                <div class="form-col-12" style="margin-top:20px;">
                                    <div class="form-group">
                                        <label>Footer Scripts (&lt;/body&gt;)</label>
                                        <input type="hidden" name="settings[footer_scripts]" id="footer_scripts_field" value="<?= h($settings['footer_scripts']) ?>">
                                        <div id="footer_scripts_editor" style="border:1px solid #ddd; border-radius:4px; min-height: 200px;"><?= h($settings['footer_scripts']) ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top:20px;">
                                <label>Custom CSS (Applied globally across all pages)</label>
                                <input type="hidden" name="settings[custom_css]" id="global_css_field" value="<?= h($settings['custom_css']) ?>">
                                <div id="global_css_editor" style="border:1px solid #ddd; border-radius:4px; min-height: 200px;"><?= h($settings['custom_css']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save All Settings</button>
            </div>
        </form>
    </div>
</div>

<style>
.tab-content { display: none; }
.tab-content.active { display: block; }
.settings-group h3 { margin: 0 0 10px; font-size: 18px; font-weight: 700; display:flex; align-items:center; gap:10px; color: #1d2327; }
.settings-group h4 { margin: 20px 0 10px; font-size: 15px; font-weight: 600; }
.filter-tab { font-size: 13px; font-weight: 600; padding: 12px 20px !important; }
</style>

<!-- Load Ace Editor for better coding experience -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>
<script>
// Initialize Ace Editors using global helper
document.addEventListener('DOMContentLoaded', function() {
    setupAceEditor("header_html_editor", "header_html_field", "html");
    setupAceEditor("header_css_editor", "header_css_field", "css");
    setupAceEditor("footer_html_editor", "footer_html_field", "html");
    setupAceEditor("footer_css_editor", "footer_css_field", "css");
    setupAceEditor("global_css_editor", "global_css_field", "css");
    setupAceEditor("header_scripts_editor", "header_scripts_field", "javascript");
    setupAceEditor("footer_scripts_editor", "footer_scripts_field", "javascript");
    
    // Resize observers for tabs
    document.querySelectorAll('#design-tabs .filter-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            document.querySelectorAll('#design-tabs .filter-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById('tab-' + target).classList.add('active');
            
            // Ace Editor Resize Fix
            window.dispatchEvent(new Event('resize'));
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
