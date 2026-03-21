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

    // Handle File Uploads (Logo & Favicon)
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['size'] > 0) {
        $up = uploadFile($_FILES['logo_upload']);
        if (isset($up['filepath'])) {
            $settingsToSave['site_logo'] = $up['filepath'];
        }
    }
    if (isset($_FILES['favicon_upload']) && $_FILES['favicon_upload']['size'] > 0) {
        $up = uploadFile($_FILES['favicon_upload']);
        if (isset($up['filepath'])) {
            $settingsToSave['site_favicon'] = $up['filepath'];
        }
    }

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
    redirect(APP_URL . '/admin/theme-settings.php' . ($_GET['tab'] ? '?tab=' . $_GET['tab'] : ''));
}

$validTabs = ['site', 'header', 'footer', 'homepage'];
$activeTab = (!empty($_GET['tab']) && in_array(trim($_GET['tab']), $validTabs)) ? trim($_GET['tab']) : 'site';
$pageTitle = 'Theme Setting';
require_once __DIR__ . '/includes/header.php';

// Get current values
$settings = [
    'site_title' => getSetting('site_title', 'My Awesome Website'),
    'site_tagline' => getSetting('site_tagline', 'Just another awesome website'),
    'site_logo' => getSetting('site_logo', ''),
    'site_favicon' => getSetting('site_favicon', ''),

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

$validTabs = ['site', 'header', 'footer', 'homepage'];
$activeTab = (!empty($_GET['tab']) && in_array(trim($_GET['tab']), $validTabs)) ? trim($_GET['tab']) : 'site';
?>

<style>
/* Modern Theme Settings UI */
.filter-tabs-container {
    background: #fff !important;
    padding: 0 20px 0 !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
.filter-tabs {
    display: flex; gap: 24px;
}
.filter-tab {
    padding: 16px 4px; cursor: pointer; color: #64748b !important; font-weight: 500; font-size: 14px; position: relative;
    border-bottom: 2px solid transparent !important; transition: all 0.2s;
    text-transform: none !important;
}
.filter-tab:hover { color: #0f172a !important; }
.filter-tab.active { color: #4f46e5 !important; border-bottom: 2px solid #4f46e5 !important; font-weight: 600; }
.filter-tab::after { display: none !important; }

/* Setting Layout */
.wp-setting-row { padding: 25px 0; display:flex; gap:30px; border-bottom: 1px solid #f1f5f9; }
.wp-setting-row:last-child { border-bottom: none; }
.wp-divider { display: none; }
.wp-label-col { width: 30%; max-width: 300px; font-weight: 600; color: #1e293b; }
.wp-label-col small.text-muted { display:block; margin-top:8px; font-weight: 400; color: #64748b; font-size: 13px; line-height:1.4; }
.wp-input-col { flex: 1; }

/* Input Sizing */
.wp-input-col input[type="text"], .wp-input-col input[type="email"], .wp-input-col select, .wp-input-col textarea {
    width: 100%;
    max-width: 600px;
    padding: 10px 14px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    font-size: 14px; color: #334155;
    background: #fff; transition: border-color 0.2s;
}
.wp-input-col input:focus, .wp-input-col textarea:focus { border-color: #4f46e5; outline:none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

/* Image Pickers */
.image-setting-preview { border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; background: #f8fafc; display: flex; gap: 20px; align-items: center; width:fit-content; }
.preview-box { background: #fff; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 15px; min-width: 120px; min-height: 80px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 13px; }
.control-box button { background: white; margin-right: 10px; }
.control-box small { font-family: monospace; }

/* Tab Content Visibility */
.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeInTab 0.3s ease; }
@keyframes fadeInTab { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="row">
    <div class="col-md-12">
        <form method="POST" class="admin-form" enctype="multipart/form-data">
            <?php csrfField(); ?>
            <div class="admin-page-header">
                <div class="header-left">
                    <h2>Theme Setting</h2>
                    <p class="text-muted">Manage your site's identity, global header, footer, and homepage aesthetics.
                    </p>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save All Changes</button>
            </div>

            <div class="form-card" style="padding:0; overflow:hidden;">
                <!-- Internal Tabs Like Screenshot -->
                <div class="filter-tabs-container"
                    style="padding: 10px 20px 0; background: #f6f7f7; border-bottom: 1px solid #c3c4c7;">
                    <div class="filter-tabs" id="design-tabs">
                        <div class="filter-tab <?=($activeTab === 'site') ? 'active' : ''?>" data-target="site">Site
                        </div>
                        <div class="filter-tab <?=($activeTab === 'header') ? 'active' : ''?>" data-target="header">
                            Header</div>
                        <div class="filter-tab <?=($activeTab === 'footer') ? 'active' : ''?>" data-target="footer">
                            Footer</div>
                        <div class="filter-tab <?=($activeTab === 'homepage') ? 'active' : ''?>"
                            data-target="homepage">Homepage</div>
                    </div>
                </div>

                <div style="padding: 25px;">
                    <!-- SITE TAB -->
                    <div id="tab-site" class="tab-content <?= $activeTab === 'site' ? 'active' : ''?>">
                        <div class="settings-group">
                            <div class="wp-settings-table">
                                <!-- Row: Site Title -->
                                <div class="wp-setting-row">
                                    <div class="wp-label-col">
                                        <label>Site Title</label>
                                        <small class="text-muted">Shown in browser tabs and search results.</small>
                                    </div>
                                    <div class="wp-input-col">
                                        <input type="text" name="settings[site_title]"
                                            value="<?= h($settings['site_title'])?>" class="form-control regular-text"
                                            placeholder="e.g. My Website">
                                    </div>
                                </div>

                                <!-- Row: Site Tagline -->
                                <div class="wp-setting-row">
                                    <div class="wp-label-col">
                                        <label>Tagline</label>
                                        <small class="text-muted">In a few words, explain what this site is
                                            about.</small>
                                    </div>
                                    <div class="wp-input-col">
                                        <input type="text" name="settings[site_tagline]"
                                            value="<?= h($settings['site_tagline'])?>"
                                            class="form-control regular-text" placeholder="e.g. Best services in town">
                                    </div>
                                </div>

                                <hr class="wp-divider">

                                <!-- Row: Logo -->
                                <div class="wp-setting-row">
                                    <div class="wp-label-col">
                                        <label>Website Logo</label>
                                        <small class="text-muted">Upload an image to represent your brand.</small>
                                    </div>
                                    <div class="wp-input-col">
                                        <div class="image-setting-preview">
                                            <div class="preview-box" id="logo-preview-box">
                                                <?php if ($settings['site_logo']): ?>
                                                <img src="<?= APP_URL . '/' . h($settings['site_logo'])?>"
                                                    id="logo-img-tag"
                                                    style="max-height: 80px; max-width: 200px; display: block;">
                                                <?php
else: ?>
                                                <div class="placeholder"><i class="fas fa-image"></i> No Logo</div>
                                                <?php
endif; ?>
                                            </div>
                                            <div class="control-box">
                                                <input type="hidden" name="settings[site_logo]" id="site_logo_input"
                                                    value="<?= h($settings['site_logo'])?>">
                                                <button type="button" class="btn btn-outline"
                                                    onclick="document.getElementById('logo_upload_field').click()">
                                                    <i class="fas fa-upload"></i> Upload Image
                                                </button>
                                                <input type="file" name="logo_upload" id="logo_upload_field"
                                                    style="display:none;"
                                                    onchange="previewLocalImage(this, 'logo-img-tag', 'logo-preview-box')">
                                                <?php if ($settings['site_logo']): ?>
                                                <button type="button" class="btn btn-outline text-danger"
                                                    onclick="removeImage('site_logo_input', 'logo-preview-box')"><i
                                                        class="fas fa-trash"></i></button>
                                                <?php
endif; ?>
                                                <div style="margin-top: 5px;"><small class="text-muted">Path:
                                                        <?= h($settings['site_logo'] ?: 'none')?>
                                                    </small></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Row: FavIcon -->
                                <div class="wp-setting-row">
                                    <div class="wp-label-col">
                                        <label>Fav Icon</label>
                                        <small class="text-muted">Small icon shown in browser tabs (Recommended:
                                            32x32px).</small>
                                    </div>
                                    <div class="wp-input-col">
                                        <div class="image-setting-preview">
                                            <div class="preview-box icon-preview" id="favicon-preview-box">
                                                <?php if ($settings['site_favicon']): ?>
                                                <img src="<?= APP_URL . '/' . h($settings['site_favicon'])?>"
                                                    id="favicon-img-tag"
                                                    style="width: 32px; height: 32px; display: block;">
                                                <?php
else: ?>
                                                <div class="placeholder"><i class="fas fa-shapes"></i></div>
                                                <?php
endif; ?>
                                            </div>
                                            <div class="control-box">
                                                <input type="hidden" name="settings[site_favicon]"
                                                    id="site_favicon_input" value="<?= h($settings['site_favicon'])?>">
                                                <button type="button" class="btn btn-outline"
                                                    onclick="document.getElementById('favicon_upload_field').click()">
                                                    <i class="fas fa-upload"></i> Upload Icon
                                                </button>
                                                <input type="file" name="favicon_upload" id="favicon_upload_field"
                                                    style="display:none;"
                                                    onchange="previewLocalImage(this, 'favicon-img-tag', 'favicon-preview-box')">
                                                <?php if ($settings['site_favicon']): ?>
                                                <button type="button" class="btn btn-outline text-danger"
                                                    onclick="removeImage('site_favicon_input', 'favicon-preview-box')"><i
                                                        class="fas fa-trash"></i></button>
                                                <?php
endif; ?>
                                                <div style="margin-top: 5px;"><small class="text-muted">Path:
                                                        <?= h($settings['site_favicon'] ?: 'none')?>
                                                    </small></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- HEADER TAB -->
                    <div id="tab-header" class="tab-content <?= $activeTab === 'header' ? 'active' : ''?>">
                        <div class="settings-group">
                            <h3><i class="fas fa-window-maximize"></i> Global Header Injection</h3>
                            <p class="text-muted">You can override the default header with custom HTML/CSS here.</p>

                            <div class="form-group" style="margin: 20px 0;">
                                <label class="checkbox-label" style="display:flex; align-items:center; gap:10px;">
                                    <input type="checkbox" name="settings[enable_custom_header]" value="1"
                                        <?=$settings['enable_custom_header'] ? 'checked' : ''?>>
                                    Enable Custom Header Design
                                </label>
                            </div>

                            <div class="form-row">
                                <div class="form-col-8">
                                    <div class="form-group">
                                        <label>Header HTML</label>
                                        <input type="hidden" name="settings[custom_header_html]" id="header_html_field"
                                            value="<?= h($settings['custom_header_html'])?>">
                                        <div id="header_html_editor" style="border:1px solid #ddd; border-radius:4px;">
                                            <?= h($settings['custom_header_html'])?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-col-4">
                                    <div class="form-group">
                                        <label>Header CSS</label>
                                        <input type="hidden" name="settings[custom_header_css]" id="header_css_field"
                                            value="<?= h($settings['custom_header_css'])?>">
                                        <div id="header_css_editor" style="border:1px solid #ddd; border-radius:4px;">
                                            <?= h($settings['custom_header_css'])?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER TAB -->
                    <div id="tab-footer" class="tab-content <?= $activeTab === 'footer' ? 'active' : ''?>">
                        <div class="settings-group">
                            <h3><i class="fas fa-window-minimize"></i> Global Footer Settings</h3>
                            <div class="form-row">
                                <div class="form-col-8">
                                    <div class="form-group">
                                        <label>Footer Description</label>
                                        <textarea name="settings[footer_desc]" class="form-control"
                                            rows="3"><?= h($settings['footer_desc'])?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Company Address</label>
                                        <input type="text" name="settings[footer_address]"
                                            value="<?= h($settings['footer_address'])?>" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col-4">
                                    <div class="form-group">
                                        <label>Contact Email</label>
                                        <input type="email" name="settings[footer_email]"
                                            value="<?= h($settings['footer_email'])?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Contact Phone</label>
                                        <input type="text" name="settings[footer_phone]"
                                            value="<?= h($settings['footer_phone'])?>" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

                            <h4>Custom Footer Injection</h4>
                            <div class="form-group" style="margin: 15px 0;">
                                <label class="checkbox-label" style="display:flex; align-items:center; gap:10px;">
                                    <input type="checkbox" name="settings[enable_custom_footer]" value="1"
                                        <?=$settings['enable_custom_footer'] ? 'checked' : ''?>>
                                    Enable Custom Footer Design
                                </label>
                            </div>
                            <div class="form-row">
                                <div class="form-col-8">
                                    <div class="form-group">
                                        <label>Footer HTML</label>
                                        <input type="hidden" name="settings[custom_footer_html]" id="footer_html_field"
                                            value="<?= h($settings['custom_footer_html'])?>">
                                        <div id="footer_html_editor" style="border:1px solid #ddd; border-radius:4px;">
                                            <?= h($settings['custom_footer_html'])?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-col-4">
                                    <div class="form-group">
                                        <label>Footer CSS</label>
                                        <input type="hidden" name="settings[custom_footer_css]" id="footer_css_field"
                                            value="<?= h($settings['custom_footer_css'])?>">
                                        <div id="footer_css_editor" style="border:1px solid #ddd; border-radius:4px;">
                                            <?= h($settings['custom_footer_css'])?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HOMEPAGE TAB -->
                    <div id="tab-homepage" class="tab-content <?= $activeTab === 'homepage' ? 'active' : ''?>">
                        <div class="settings-group">
                            <h3><i class="fas fa-home"></i> Frontend Navigation Logic</h3>

                            <div class="form-group">
                                <label>Your homepage displays</label>
                                <select name="settings[front_page_id]" class="form-control" style="width: 300px;">
                                    <option value="0">--- Select Home Page ---</option>
                                    <?php foreach ($allPagesList as $p): ?>
                                    <option value="<?= $p['id']?>" <?=$settings['front_page_id']==$p['id'] ? 'selected'
                                        : ''?>>
                                        <?= h($p['title'])?>
                                    </option>
                                    <?php
endforeach; ?>
                                </select>
                            </div>

                            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

                            <h4>Permalink Structure</h4>
                            <div class="form-group"
                                style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                                <label style="display:flex; align-items:center; gap:10px;"><input type="radio"
                                        name="settings[permalink_structure]" value="plain"
                                        <?=$settings['permalink_structure']==='plain' ? 'checked' : ''?>> Plain
                                    (/?p=123)</label>
                                <label style="display:flex; align-items:center; gap:10px;"><input type="radio"
                                        name="settings[permalink_structure]" value="post_name"
                                        <?=$settings['permalink_structure']==='post_name' ? 'checked' : ''?>> Post Name
                                    (/sample-post)</label>
                                <label style="display:flex; align-items:center; gap:10px;"><input type="radio"
                                        name="settings[permalink_structure]" value="category_post_name"
                                        <?=$settings['permalink_structure']==='category_post_name' ? 'checked' : ''?>>
                                    Category & Post Name (/category/sample-post)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save All
                    Settings</button>
            </div>
        </form>
    </div>
</div>


<!-- Load Ace Editor for better coding experience -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>
<script>
    // Initialize Ace Editors using global helper
    document.addEventListener('DOMContentLoaded', function () {
        setupAceEditor("header_html_editor", "header_html_field", "html");
        setupAceEditor("header_css_editor", "header_css_field", "css");
        setupAceEditor("footer_html_editor", "footer_html_field", "html");
        setupAceEditor("footer_css_editor", "footer_css_field", "css");
        setupAceEditor("global_css_editor", "global_css_field", "css");

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

    function previewLocalImage(input, imgTagId, boxId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                let box = document.getElementById(boxId);
                let img = document.getElementById(imgTagId);
                if (!img) {
                    box.innerHTML = `<img src="${e.target.result}" id="${imgTagId}" style="max-height: 80px; max-width: 200px; display: block;">`;
                    if (boxId.includes('icon')) {
                        document.getElementById(imgTagId).style.width = '32px';
                        document.getElementById(imgTagId).style.height = '32px';
                    }
                } else {
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage(inputId, boxId) {
        document.getElementById(inputId).value = "";
        document.getElementById(boxId).innerHTML = '<div class="placeholder"><i class="fas fa-image"></i> No Image</div>';
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>