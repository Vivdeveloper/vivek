<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requirePermission('custom_code');

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    requireEditAccess();
    updateSetting('custom_code_header', $_POST['header_code'] ?? '');
    updateSetting('custom_code_body', $_POST['body_code'] ?? '');
    updateSetting('custom_code_footer', $_POST['footer_code'] ?? '');
    updateSetting('custom_css', $_POST['custom_css'] ?? '');
    
    setFlash('success', 'Custom Code updated successfully!');
    redirect(APP_URL . '/admin/custom-code.php');
}

$pageTitle = 'Custom Code';
require_once __DIR__ . '/includes/header.php';

// Get Current Settings
$header_code = getSetting('custom_code_header', '');
$body_code = getSetting('custom_code_body', '');
$footer_code = getSetting('custom_code_footer', '');
$custom_css = getSetting('custom_css', '');
?>

<div class="wrap">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Custom Code & Scripts</h2>
            <p class="text-muted">Inject global header/footer scripts, tracking pixels, and custom CSS without touching files.</p>
        </div>
    </div>

    <form id="custom-code-form" action="" method="POST">
        <?php csrfField(); ?>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div style="display: flex; flex-direction: column; gap: 25px;">
                
                <!-- Header Code -->
                <div class="admin-card">
                    <div class="admin-card-header"><i class="fas fa-chevron-up" style="margin-right:8px;"></i> Header Scripts</div>
                    <div style="padding: 24px;">
                        <p class="text-muted" style="margin-bottom: 12px; font-size:12px;">Appears before <code>&lt;/head&gt;</code>. Ideal for Google Analytics (GA4), Meta Pixel, and Verification Meta tags.</p>
                        <textarea name="header_code" rows="10" style="width:100%; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; padding: 15px; background: #fafafa; border: 1px solid #ddd; border-radius: 6px;" placeholder="<!-- Global site tag (gtag.js) - Google Analytics -->"><?= h($header_code) ?></textarea>
                    </div>
                </div>

                <!-- Body Open Code -->
                <div class="admin-card">
                    <div class="admin-card-header"><i class="fas fa-terminal" style="margin-right:8px;"></i> Body Start Scripts</div>
                    <div style="padding: 24px;">
                        <p class="text-muted" style="margin-bottom: 12px; font-size:12px;">Appears immediately after <code>&lt;body&gt;</code>. Ideal for Google Tag Manager noscript fallbacks.</p>
                        <textarea name="body_code" rows="5" style="width:100%; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; padding: 15px; background: #fafafa; border: 1px solid #ddd; border-radius: 6px;"><?= h($body_code) ?></textarea>
                    </div>
                </div>

                <!-- Footer Code -->
                <div class="admin-card">
                    <div class="admin-card-header"><i class="fas fa-chevron-down" style="margin-right:8px;"></i> Footer Scripts</div>
                    <div style="padding: 24px;">
                        <p class="text-muted" style="margin-bottom: 12px; font-size:12px;">Appears before <code>&lt;/body&gt;</code>. Ideal for Chat Widgets (Intercom, Crisp) and high-load scripts.</p>
                        <textarea name="footer_code" rows="10" style="width:100%; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; padding: 15px; background: #fafafa; border: 1px solid #ddd; border-radius: 6px;" placeholder="<!-- Floating chat code here -->"><?= h($footer_code) ?></textarea>
                    </div>
                </div>

                <!-- Custom CSS -->
                <div class="admin-card">
                    <div class="admin-card-header"><i class="fab fa-css3-alt" style="margin-right:8px;"></i> Global CSS</div>
                    <div style="padding: 24px;">
                        <p class="text-muted" style="margin-bottom: 12px; font-size:12px;">Override any styles on your site without creating a stylesheet.</p>
                        <textarea name="custom_css" rows="15" style="width:100%; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; padding: 15px; background: #1e1e1e; color: #d4d4d4; border: none; border-radius: 6px;" placeholder="/* Add your custom styles here */
body { font-family: 'Inter', sans-serif; }"><?= h($custom_css) ?></textarea>
                    </div>
                </div>

            </div>

            <!-- Sidebar Actions -->
            <div style="display:flex; flex-direction: column; gap: 20px; position: sticky; top: 100px; height: fit-content;">
                <div class="admin-card">
                    <div class="admin-card-header">Publish Changes</div>
                    <div style="padding: 24px;">
                        <?php if (canEdit()): ?>
                            <p class="text-muted" style="font-size: 11px; margin-bottom:15px;">Caution: Adding malformed script tags here can break your frontend layout.</p>
                            <button type="submit" class="btn btn-primary" style="width:100%; height: 44px; font-weight:600;">
                                <i class="fas fa-save" style="margin-right: 8px;"></i> Save All Code
                            </button>
                        <?php else: ?>
                            <div style="background: #fff8e5; border: 1px solid #ffb900; padding: 10px; border-radius: 6px; color: #856404; font-size: 11px;">
                                <i class="fas fa-eye" style="margin-right: 5px;"></i> **Read-Only Mode**<br>
                                You cannot modify global scripts.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if (canEdit()): ?>
<script>
// Save shortcut
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('custom-code-form').submit();
    }
});
</script>
<?php endif; ?>

<style>
.admin-card-header { padding: 12px 20px; font-weight: 600; color: #1d2327; border-bottom: 1px solid #f0f0f1; background: #fff; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
