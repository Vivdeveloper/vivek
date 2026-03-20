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
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $focusKeyword = trim($_POST['focus_keyword'] ?? '');

    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
        $upload = uploadFile($_FILES['featured_image']);
        if (isset($upload['filepath'])) { $featuredImage = $upload['filepath']; }
    }

    if ($title) {
        try {
            $stmt = db()->prepare("INSERT INTO pages (title, slug, content, template, status, featured_image, custom_css, meta_title, meta_description, focus_keyword) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $template, $status, $featuredImage, $customCss, $metaTitle, $metaDescription, $focusKeyword]);
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

                <!-- SEO ANALYSIS TOOL -->
                <div class="form-card seo-card" style="margin-top: 25px;">
                    <div class="seo-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                        <h3 style="margin:0;"><i class="fas fa-search" style="color:#2271b1;"></i> SEO Optimizer (Rank Math Style)</h3>
                        <div class="seo-score-badge" style="background:var(--bg-secondary); border:1px solid #dce4f5; padding:10px 20px; border-radius:30px; display:flex; align-items:center; gap:10px;">
                            <div class="score-circle" id="seo-score-circle" style="width:36px; height:36px; border-radius:50%; background:#ff4b4b; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:14px;">0</div>
                            <span style="font-weight:600; font-size:13px;">SEO Score</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col-12">
                            <div class="form-group">
                                <label><i class="fas fa-bullseye" style="color:#2271b1; opacity:0.7;"></i> Focus Keyword</label>
                                <input type="text" name="focus_keyword" id="focus_keyword" class="form-control" placeholder="e.g. digital marketing agency Mumbai" style="padding:12px; font-weight:500;">
                            </div>
                        </div>
                    </div>

                    <div class="seo-preview-box" style="background:#f6f7f7; padding:20px; border:1px solid #dce4f5; border-radius:8px; margin-bottom:25px;">
                        <div style="margin-bottom:15px; font-size:11px; text-transform:uppercase; font-weight:700; color:#50575e;">Google Search Preview</div>
                        <div class="google-preview" style="max-width:600px;">
                            <div id="preview-title" style="font-size:19px; color:#1a0dab; line-height:1.2; margin-bottom:4px; font-family: arial, sans-serif; cursor:pointer;">
                                Page Title | VivFramework
                            </div>
                            <div id="preview-url" style="font-size:14px; color:#006621; line-height:1.3; margin-bottom:4px; font-family: arial, sans-serif;">
                                <?= APP_URL ?>/your-page-url
                            </div>
                            <div id="preview-desc" style="font-size:14px; color:#545454; line-height:1.4; font-family: arial, sans-serif;">
                                Please provide a meta description for your page...
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col-6">
                            <div class="form-group">
                                <label>SEO Title</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="SEO Title...">
                            </div>
                        </div>
                        <div class="form-col-6">
                            <div class="form-group">
                                <label>SEO Description</label>
                                <textarea name="meta_description" id="meta_description" rows="3" class="form-control" placeholder="SEO Description..."></textarea>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:25px 0; border:0; border-top:1px solid #eee;">
                    <h4 style="font-size:14px; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-list-check" style="color:#2271b1;"></i> SEO Analysis Checklist
                    </h4>
                    <div class="seo-checklist" id="seo-checklist" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;"></div>
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
    titleIn.addEventListener('input', () => { 
        slugIn.value = titleIn.value.toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]+/g, ''); 
        updateSEOScore();
    });

    // --- SEO ANALYSIS ---
    function updateSEOScore() {
        const title = document.getElementById('title').value;
        const slug = document.getElementById('slug').value;
        const focus = document.getElementById('focus_keyword').value.trim().toLowerCase();
        const metaTitle = document.getElementById('meta_title').value || title;
        const metaDesc = document.getElementById('meta_description').value;
        const content = document.getElementById('raw-html-editor').value;
        const textOnly = content.replace(/<[^>]*>?/gm, '');

        let score = 0;
        let checks = [];

        if (focus && metaTitle.toLowerCase().includes(focus)) { score += 20; checks.push({ label: 'Keyword in SEO Title', status: 'pass' }); }
        else { checks.push({ label: 'Keyword in SEO Title', status: focus ? 'fail' : 'pending' }); }

        if (focus && metaDesc.toLowerCase().includes(focus)) { score += 15; checks.push({ label: 'Keyword in Description', status: 'pass' }); }
        else { checks.push({ label: 'Keyword in Description', status: focus ? 'fail' : 'pending' }); }

        if (focus && slug.toLowerCase().includes(focus.replace(/\s+/g, '-'))) { score += 10; checks.push({ label: 'Keyword in URL', status: 'pass' }); }
        else { checks.push({ label: 'Keyword in URL', status: focus ? 'fail' : 'pending' }); }

        const wordCount = textOnly.trim().split(/\s+/).length || 0;
        if (wordCount > 300) { score += 20; checks.push({ label: 'Word count (>300): ' + wordCount, status: 'pass' }); }
        else { checks.push({ label: 'Word count: ' + wordCount, status: 'fail' }); }

        if (focus && wordCount > 0) {
            const count = (textOnly.toLowerCase().match(new RegExp(focus, 'g')) || []).length;
            const density = (count / wordCount) * 100;
            if (density > 0.5 && density < 3) { score += 15; checks.push({ label: 'Density: ' + density.toFixed(2) + '%', status: 'pass' }); }
            else { checks.push({ label: 'Density: ' + density.toFixed(2) + '%', status: 'fail' }); }
        }

        if (metaDesc.length > 50 && metaDesc.length < 160) { score += 10; checks.push({ label: 'Description Length', status: 'pass' }); }
        else { checks.push({ label: 'Description Length', status: 'fail' }); }

        if (metaTitle.length > 30 && metaTitle.length < 60) { score += 10; checks.push({ label: 'Title Length', status: 'pass' }); }
        else { checks.push({ label: 'Title Length', status: 'fail' }); }

        const circle = document.getElementById('seo-score-circle');
        circle.textContent = score;
        circle.style.background = score > 80 ? '#10b981' : (score > 50 ? '#f59e0b' : '#ff4b4b');

        const checklist = document.getElementById('seo-checklist');
        checklist.innerHTML = '';
        checks.forEach(c => {
            const div = document.createElement('div');
            div.style.cssText = `display:flex; align-items:center; gap:8px; font-size:12px; padding:8px; border-radius:4px; background:${c.status === 'pass' ? '#effaf5' : (c.status === 'fail' ? '#fff5f5' : '#f8f9fa')}`;
            const icon = document.createElement('i');
            icon.className = c.status === 'pass' ? 'fas fa-check-circle' : (c.status === 'fail' ? 'fas fa-times-circle' : 'far fa-circle');
            icon.style.color = c.status === 'pass' ? '#10b981' : (c.status === 'fail' ? '#ef4444' : '#64748b');
            div.appendChild(icon);
            div.appendChild(document.createTextNode(c.label));
            checklist.appendChild(div);
        });

        document.getElementById('preview-title').textContent = metaTitle + ' | VivFramework';
        document.getElementById('preview-desc').textContent = metaDesc || 'Please provide a meta description...';
        document.getElementById('preview-url').textContent = '<?= APP_URL ?>/' + (slug || 'your-page-url');
    }

    ['slug', 'focus_keyword', 'meta_title', 'meta_description', 'raw-html-editor'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateSEOScore);
    });
    updateSEOScore();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
