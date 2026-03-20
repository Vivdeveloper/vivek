<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requirePermission('floating_cta');

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    updateSetting('cta_enabled', $_POST['cta_enabled'] ?? '0');
    updateSetting('cta_design_mobile', $_POST['cta_design_mobile'] ?? 'simple');
    updateSetting('cta_design_desktop', $_POST['cta_design_desktop'] ?? 'pill');
    
    // Call Settings
    updateSetting('cta_show_call', $_POST['cta_show_call'] ?? '0');
    updateSetting('cta_phone', $_POST['cta_phone'] ?? '');
    updateSetting('cta_text_call', $_POST['cta_text_call'] ?? 'Call Now');
    updateSetting('cta_bg_call', $_POST['cta_bg_call'] ?? '#2271b1');
    updateSetting('cta_visibility_call', $_POST['cta_visibility_call'] ?? 'mobile');
    
    // WhatsApp Settings
    updateSetting('cta_show_whatsapp', $_POST['cta_show_whatsapp'] ?? '0');
    updateSetting('cta_whatsapp', $_POST['cta_whatsapp'] ?? '');
    updateSetting('cta_text_whatsapp', $_POST['cta_text_whatsapp'] ?? 'WhatsApp');
    updateSetting('cta_bg_whatsapp', $_POST['cta_bg_whatsapp'] ?? '#25d366');
    updateSetting('cta_visibility_wa', $_POST['cta_visibility_wa'] ?? 'mobile');
    
    setFlash('success', 'CTA Layout updated successfully!');
    redirect(APP_URL . '/admin/cta-buttons.php');
}

$pageTitle = 'Floating CTA Buttons';
require_once __DIR__ . '/includes/header.php';

// Get Current Settings
$cta_enabled = getSetting('cta_enabled', '0');
$cta_design_mobile = getSetting('cta_design_mobile', 'simple');
$cta_design_desktop = getSetting('cta_design_desktop', 'pill');

$cta_show_call = getSetting('cta_show_call', '1');
$cta_phone = getSetting('cta_phone', '');
$cta_text_call = getSetting('cta_text_call', 'Call Now');
$cta_bg_call = getSetting('cta_bg_call', '#2271b1');
$cta_visibility_call = getSetting('cta_visibility_call', 'mobile');

$cta_show_whatsapp = getSetting('cta_show_whatsapp', '1');
$cta_whatsapp = getSetting('cta_whatsapp', '');
$cta_text_whatsapp = getSetting('cta_text_whatsapp', 'WhatsApp');
$cta_bg_whatsapp = getSetting('cta_bg_whatsapp', '#25d366');
$cta_visibility_wa = getSetting('cta_visibility_wa', 'mobile');
?>

<div class="wrap">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Floating CTA Buttons</h2>
            <p class="text-muted">Direct control over your site's interaction patterns</p>
        </div>
    </div>

    <form id="cta-settings-form" action="" method="POST">
        <?php csrfField(); ?>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div class="admin-card">
                <div class="admin-card-header">Interaction Styles & Configuration</div>
                <div style="padding: 24px;">
                    
                    <!-- Separate Selections for Mobile vs Desktop -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 35px; background: rgba(0,0,0,0.02); padding: 25px; border-radius: 12px; border: 1px solid #e2e4e7;">
                        
                        <!-- Mobile Design Selection -->
                        <div>
                            <h4 style="margin-bottom: 15px; color: #1d2327; font-size: 14px;"><i class="fas fa-mobile-alt"></i> Mobile Layout Style</h4>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <label class="style-choice <?= $cta_design_mobile === 'simple' ? 'active' : '' ?>">
                                    <input type="radio" name="cta_design_mobile" value="simple" <?= $cta_design_mobile === 'simple' ? 'checked' : '' ?>>
                                    <div class="choice-content">
                                        <div class="choice-preview mobile-simple"><span></span><span></span></div>
                                        <div class="choice-text">Classic Split (High Conversion)</div>
                                    </div>
                                </label>
                                <label class="style-choice <?= $cta_design_mobile === 'pill' ? 'active' : '' ?>">
                                    <input type="radio" name="cta_design_mobile" value="pill" <?= $cta_design_mobile === 'pill' ? 'checked' : '' ?>>
                                    <div class="choice-content">
                                        <div class="choice-preview mobile-pill"><span></span><span></span></div>
                                        <div class="choice-text">Modern Pill (Floating Unit)</div>
                                    </div>
                                </label>
                                <label class="style-choice <?= $cta_design_mobile === 'gradient' ? 'active' : '' ?>">
                                    <input type="radio" name="cta_design_mobile" value="gradient" <?= $cta_design_mobile === 'gradient' ? 'checked' : '' ?>>
                                    <div class="choice-content">
                                        <div class="choice-preview mobile-gradient"></div>
                                        <div class="choice-text">Gradient Bar (Seamless)</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Desktop Design Selection -->
                        <div>
                            <h4 style="margin-bottom: 15px; color: #1d2327; font-size: 14px;"><i class="fas fa-desktop"></i> Desktop Layout Style</h4>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <label class="style-choice <?= $cta_design_desktop === 'simple' ? 'active' : '' ?>">
                                    <input type="radio" name="cta_design_desktop" value="simple" <?= $cta_design_desktop === 'simple' ? 'checked' : '' ?>>
                                    <div class="choice-content">
                                        <div class="choice-preview desktop-simple"><span></span><span></span></div>
                                        <div class="choice-text">Full Bottom Bar (Split)</div>
                                    </div>
                                </label>
                                <label class="style-choice <?= $cta_design_desktop === 'pill' ? 'active' : '' ?>">
                                    <input type="radio" name="cta_design_desktop" value="pill" <?= $cta_design_desktop === 'pill' ? 'checked' : '' ?>>
                                    <div class="choice-content">
                                        <div class="choice-preview desktop-pill"><span></span><span></span></div>
                                        <div class="choice-text">Corner Action Circles (Dots)</div>
                                    </div>
                                </label>
                                <label class="style-choice <?= $cta_design_desktop === 'gradient' ? 'active' : '' ?>">
                                    <input type="radio" name="cta_design_desktop" value="gradient" <?= $cta_design_desktop === 'gradient' ? 'checked' : '' ?>>
                                    <div class="choice-content">
                                        <div class="choice-preview desktop-gradient"><span></span><span></span></div>
                                        <div class="choice-text">Centered Gradient unit</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                    </div>

                    <!-- Call & WhatsApp Settings Unified Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px 40px; border-top: 1px solid #f0f0f1; padding-top: 25px;">
                        <!-- Call Options Column -->
                        <div style="border-right: 1px solid #f0f0f1; padding-right: 20px;">
                            <h4 style="margin-bottom: 15px; color:#1d2327;"><i class="fas fa-phone-alt"></i> Call Button</h4>
                            <div class="form-group"><label>Phone Number</label><input type="text" name="cta_phone" value="<?= h($cta_phone) ?>" placeholder="+91 9999999999"></div>
                            <div class="form-group"><label>Button Text</label><input type="text" name="cta_text_call" value="<?= h($cta_text_call) ?>"></div>
                            <div class="form-group"><label>Color</label><input type="color" name="cta_bg_call" value="<?= h($cta_bg_call) ?>" style="height: 38px; width:100%; border:none; background:none;"></div>
                            <div class="form-group">
                                <label>Visibility</label>
                                <select name="cta_visibility_call" style="width:100%;">
                                    <option value="mobile" <?= $cta_visibility_call === 'mobile' ? 'selected' : '' ?>>Mobile Only</option>
                                    <option value="desktop" <?= $cta_visibility_call === 'desktop' ? 'selected' : '' ?>>Desktop Only</option>
                                    <option value="both" <?= $cta_visibility_call === 'both' ? 'selected' : '' ?>>All Devices</option>
                                </select>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px;">
                                <label style="margin:0; font-weight:600;">Enable Call</label>
                                <label class="switch"><input type="checkbox" name="cta_show_call" value="1" <?= $cta_show_call === '1' ? 'checked' : '' ?>><span class="slider round"></span></label>
                            </div>
                        </div>

                        <!-- WhatsApp Options Column -->
                        <div>
                            <h4 style="margin-bottom: 15px; color:#1d2327;"><i class="fab fa-whatsapp"></i> WhatsApp Button</h4>
                            <div class="form-group"><label>Number</label><input type="text" name="cta_whatsapp" value="<?= h($cta_whatsapp) ?>" placeholder="919999999999"></div>
                            <div class="form-group"><label>Button Text</label><input type="text" name="cta_text_whatsapp" value="<?= h($cta_text_whatsapp) ?>"></div>
                            <div class="form-group"><label>Color</label><input type="color" name="cta_bg_whatsapp" value="<?= h($cta_bg_whatsapp) ?>" style="height: 38px; width:100%; border:none; background:none;"></div>
                            <div class="form-group">
                                <label>Visibility</label>
                                <select name="cta_visibility_wa" style="width:100%;">
                                    <option value="mobile" <?= $cta_visibility_wa === 'mobile' ? 'selected' : '' ?>>Mobile Only</option>
                                    <option value="desktop" <?= $cta_visibility_wa === 'desktop' ? 'selected' : '' ?>>Desktop Only</option>
                                    <option value="both" <?= $cta_visibility_wa === 'both' ? 'selected' : '' ?>>All Devices</option>
                                </select>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px;">
                                <label style="margin:0; font-weight:600;">Enable WhatsApp</label>
                                <label class="switch"><input type="checkbox" name="cta_show_whatsapp" value="1" <?= $cta_show_whatsapp === '1' ? 'checked' : '' ?>><span class="slider round"></span></label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div style="display:flex; flex-direction: column; gap: 20px; position: sticky; top: 100px; height: fit-content;">
                <div class="admin-card">
                    <div class="admin-card-header">Global Power</div>
                    <div style="padding: 24px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #fff; border: 1px solid #e2e4e7; border-radius: 6px;">
                            <label style="margin:0; font-weight:600;">System Active</label>
                            <label class="switch" style="width: 50px; height: 26px;">
                                <input type="checkbox" name="cta_enabled" value="1" <?= $cta_enabled === '1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <div style="padding: 20px;">
                        <button type="submit" class="btn btn-primary" style="width:100%; height: 44px; font-weight:600;">
                            <i class="fas fa-save" style="margin-right: 8px;"></i> Save All Layouts
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Toggle 'active' class visually
document.querySelectorAll('.style-choice input').forEach(radio => {
    radio.addEventListener('change', function() {
        this.closest('div').querySelectorAll('.style-choice').forEach(l => l.classList.remove('active'));
        this.parentElement.classList.add('active');
    });
});

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('cta-settings-form').submit();
    }
});
</script>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
