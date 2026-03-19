<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (!$slug) { $slug = $title; }
    $slug = slugify($slug);
    $content = $_POST['content'] ?? '';
    $template = $_POST['template'] ?? 'default';
    $status = $_POST['status'] ?? 'draft';
    $customCss = $_POST['custom_css'] ?? '';

    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
        $upload = uploadFile($_FILES['featured_image']);
        if (isset($upload['filepath'])) { $featuredImage = $upload['filepath']; }
    }

    if ($title) {
        try {
            $stmt = db()->prepare("INSERT INTO pages (title, slug, content, template, status, featured_image, custom_css) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $template, $status, $featuredImage, $customCss]);
            $pageId = db()->lastInsertId();
            setFlash('success', 'Page created!');
            redirect(APP_URL . '/admin/page-edit.php?id=' . $pageId);
        } catch (PDOException $e) { setFlash('error', 'A page with this title/slug may already exist.'); }
    }
}

$pageTitle = 'Create Page';
require_once __DIR__ . '/includes/header.php';
?>
<style>
    .editor-container { position: relative; margin-bottom: 20px; }
    .editor-tabs { display: flex; gap: 5px; margin-bottom: -1px; }
    .editor-tab { padding: 10px 20px; background: #fdfdfd; border: 1px solid #dce4f5; border-bottom: none; border-radius: 8px 8px 0 0; cursor: pointer; font-size: 13px; font-weight: 500; color: #666; transition: all 0.2s; }
    .editor-tab.active { background: #fff; color: #2271b1; border-bottom: 2px solid #2271b1; }
    .editor-pane { display: none; background: #fff; border: 1px solid #dce4f5; border-radius: 0 8px 8px 8px; padding: 1px; min-height: 500px; }
    .editor-pane.active { display: block; }
    #raw-html-editor, #custom-css-editor { width: 100%; height: 600px; border: none; padding: 15px; font-family: 'Courier New', monospace; font-size: 14px; color: #1d2327; background: #fafafa; outline: none; resize: vertical; }
    #custom-css-editor { background: #fcfcfc; color: #2c3e50; }
</style>

<div class="admin-page">
    <div class="admin-page-header">
        <h2>Create New Page</h2>
        <a href="<?= APP_URL ?>/admin/pages.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
        <?php csrfField(); ?>
        <div class="form-row">
            <div class="form-col-8">
                <div class="form-card">
                    <div class="form-group">
                        <label>Title *</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="title" name="title" required placeholder="Enter page title..." style="font-size:18px; flex:1;">
                            <button type="button" id="btn-ai-titlify" class="btn btn-outline" style="white-space:nowrap;"><i class="fas fa-magic"></i> AI Title</button>
                        </div>
                    </div>
                    <div class="form-group"><label>Slug (URL Path)</label><input type="text" id="slug" name="slug"></div>
                    
                    <!-- AI Page Writer -->
                    <div class="ai-generator-panel" style="background: linear-gradient(135deg, #f8faff, #f0f4ff); border: 1px solid #dce4f5; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #3b5998; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-magic"></i> AI Page Writer
                        </h4>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="ai-prompt" placeholder="Topic..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;">
                            <button type="button" id="btn-ai-generate" class="btn btn-primary" style="white-space: nowrap;"><i class="fas fa-pen-nib"></i> Generate Content</button>
                        </div>
                        <div id="ai-status" style="font-size: 12px; color: #666; margin-top: 8px; display: none;">Writing...</div>

                        <div id="ai-preview-area" style="display: none; margin-top: 15px; background: white; border: 1px solid #dce4f5; border-radius: 8px; overflow: hidden;">
                            <div style="padding: 10px; background: #f0f4ff; border-bottom: 1px solid #dce4f5; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; font-size: 13px;">Article Preview</span>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" id="btn-ai-apply" class="btn btn-primary btn-sm">Apply</button>
                                    <button type="button" id="btn-ai-discard" class="btn btn-outline btn-sm">Discard</button>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr; gap: 10px; padding: 10px; max-height: 250px; overflow-y: auto;">
                                <div><small style="text-transform: uppercase; font-size: 10px; font-weight: 700;">AI Suggestion</small><div id="ai-new-preview" style="font-size: 11px; white-space: pre-wrap; background: #ebf5ff; padding: 8px;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Editor Tabs Container -->
                    <div class="editor-container">
                        <div class="editor-tabs">
                            <div class="editor-tab active" data-pane="html-pane"><i class="fas fa-code"></i> HTML Source</div>
                            <div class="editor-tab" data-pane="css-pane"><i class="fas fa-palette"></i> Custom CSS</div>
                        </div>
                        
                        <div id="html-pane" class="editor-pane active">
                            <textarea id="raw-html-editor" name="content" placeholder="Paste or write your raw HTML code here..."></textarea>
                        </div>

                        <div id="css-pane" class="editor-pane">
                            <textarea id="custom-css-editor" name="custom_css" placeholder="/* Add your custom CSS here */"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-col-4">
                <div class="form-card">
                    <h3>Page Settings</h3>
                    <div class="form-group"><label>Template</label><select name="template"><option value="default">Default</option><option value="full-width">Full Width</option><option value="canvas">Canvas</option></select></div>
                    <div class="form-group"><label>Status</label><select name="status"><option value="draft">Draft</option><option value="published">Published</option></select></div>
                    <div class="form-group"><label>Featured Image</label><input type="file" name="featured_image"></div>
                    <button type="submit" class="btn btn-primary btn-block">Create Page</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let aiBuffer = "";
let currentPane = 'html-pane';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.editor-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const targetPane = tab.getAttribute('data-pane');
            if (currentPane === targetPane) return;
            document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.editor-pane').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(targetPane).classList.add('active');
            currentPane = targetPane;
        });
    });

    document.getElementById('btn-ai-titlify')?.addEventListener('click', async function() {
        const t = document.getElementById('title').value;
        const body = new URLSearchParams();
        body.append('messages', JSON.stringify([{ role: 'user', content: 'Suggest 1 page title: ' + t }]));
        try {
            const res = await fetch("https://ubsa.in/smartprogrammers/test/z.php", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body.toString() });
            const data = await res.json();
            if (data && data.result) document.getElementById('title').value = data.result.trim().replace(/^"|"$/g, '');
        } catch(e) {}
    });

    document.getElementById('btn-ai-generate').addEventListener('click', async function() {
        const p = document.getElementById('ai-prompt').value.trim();
        if (!p) return;
        const status = document.getElementById('ai-status');
        status.style.display = 'block'; status.textContent = 'Writing...';
        try {
            const body = new URLSearchParams();
            body.append('messages', JSON.stringify([{ role: 'user', content: 'Write a web page based on topic: ' + p + '. Use basic clean HTML tags.' }]));
            const res = await fetch("https://ubsa.in/smartprogrammers/test/z.php", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body.toString() });
            const data = await res.json();
            if (data && data.result) {
                aiBuffer = data.result.replace(/```html/g, '').replace(/```/g, '').trim();
                document.getElementById('ai-new-preview').textContent = aiBuffer;
                document.getElementById('ai-preview-area').style.display = 'block';
                status.textContent = '✅ Suggested ready!';
            }
        } catch(e) { status.textContent = '❌ AI Error'; }
    });

    document.getElementById('btn-ai-apply').addEventListener('click', () => {
        document.getElementById('raw-html-editor').value = aiBuffer;
        document.getElementById('ai-preview-area').style.display = 'none';
        document.getElementById('ai-status').textContent = '✅ Applied to HTML Source!';
    });
    document.getElementById('btn-ai-discard').addEventListener('click', () => { document.getElementById('ai-preview-area').style.display = 'none'; });

    const titleIn = document.getElementById('title');
    const slugIn = document.getElementById('slug');
    titleIn.addEventListener('input', () => { slugIn.value = titleIn.value.toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]+/g, ''); });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
