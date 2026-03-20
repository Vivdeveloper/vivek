<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

$id = intval($_GET['id'] ?? 0);
$page = getPageById($id);
if (!$page) {
    redirect(APP_URL . '/admin/pages.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (!$slug) {
        $slug = $title;
    }
    $slug = slugify($slug);
    $content = $_POST['content'] ?? '';
    $template = $_POST['template'] ?? 'default';
    $status = $_POST['status'] ?? 'draft';
    $customCss = $_POST['custom_css'] ?? '';
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $focusKeyword = trim($_POST['focus_keyword'] ?? '');

    $featuredImage = $page['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
        $upload = uploadFile($_FILES['featured_image']);
        if (isset($upload['filepath'])) {
            $featuredImage = $upload['filepath'];
        }
    }

    if ($title) {
        $stmt = db()->prepare("UPDATE pages SET title=?, slug=?, content=?, template=?, status=?, featured_image=?, custom_css=?, meta_title=?, meta_description=?, focus_keyword=? WHERE id=?");
        $stmt->execute([$title, $slug, $content, $template, $status, $featuredImage, $customCss, $metaTitle, $metaDescription, $focusKeyword, $id]);
        setFlash('success', 'Page updated!');
        redirect(APP_URL . '/admin/page-edit.php?id=' . $id);
    }
}

$pageTitle = 'Edit Page';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h2>Edit Page:
            <?= h($page['title'])?>
        </h2>
        <div class="admin-page-header__actions">
            <a href="<?= pageUrl($page['slug'], $page['id'])?>" class="btn btn-outline" target="_blank"><i
                    class="fas fa-eye"></i> View Live</a>
            <a href="<?= APP_URL?>/admin/pages.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
        <?php csrfField(); ?>
        <div class="form-row">
            <div class="form-col-8">
                <div class="form-card editor-form-card">
                    <div class="form-group">
                        <label>Title *</label>
                        <div class="editor-field-row editor-field-row--title">
                            <input type="text" id="title" name="title" value="<?= h($page['title'])?>" required>
                            <button type="button" id="btn-ai-titlify" class="btn btn-outline"><i class="fas fa-magic"></i> AI Title</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Slug (URL Path)</label>
                        <div class="editor-field-row">
                            <input type="text" id="slug" name="slug" value="<?= h($page['slug'])?>">
                            <button type="button" class="btn btn-outline" data-slug-from-title><i class="fas fa-link"></i> From title</button>
                        </div>
                    </div>

                    <div class="ai-writer-panel">
                        <h4 class="ai-writer-panel__title"><i class="fas fa-magic"></i> AI Page Writer</h4>
                        <div class="editor-field-row">
                            <input type="text" id="ai-prompt" placeholder="Topic or outline for this page...">
                            <button type="button" id="btn-ai-generate" class="btn btn-primary"><i class="fas fa-pen-nib"></i> Write Content</button>
                        </div>
                        <div id="ai-status" class="ai-writer-panel__status">Writing...</div>

                        <div id="ai-preview-area" class="ai-preview-card">
                            <div class="ai-preview-card__header">
                                <span>Article Preview</span>
                                <div class="ai-preview-card__actions">
                                    <button type="button" id="btn-ai-apply" class="btn btn-primary btn-sm">Apply</button>
                                    <button type="button" id="btn-ai-discard" class="btn btn-outline btn-sm">Discard</button>
                                </div>
                            </div>
                            <div class="ai-preview-card__grid ai-preview-card__grid--single">
                                <div>
                                    <span class="ai-preview-card__label">AI suggestion</span>
                                    <div id="ai-new-preview" class="ai-preview-card__body ai-preview-card__body--accent"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="editor-container">
                        <div class="editor-tabs">
                            <div class="editor-tab active" data-pane="html-pane"><i class="fas fa-code"></i> HTML Source
                            </div>
                            <div class="editor-tab" data-pane="css-pane"><i class="fas fa-palette"></i> Custom CSS</div>
                        </div>

                        <!-- HTML Source Pane -->
                        <div id="html-pane" class="editor-pane active">
                            <textarea id="raw-html-editor" name="content"
                                placeholder="Paste or write your raw HTML code here..."><?= h($page['content'])?></textarea>
                        </div>

                        <!-- Custom CSS Pane -->
                        <div id="css-pane" class="editor-pane">
                            <textarea id="custom-css-editor" name="custom_css"
                                placeholder="/* Add your custom CSS here */"><?= h($page['custom_css'] ?? '')?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SEO ANALYSIS TOOL -->
                <?php include __DIR__ . '/includes/seo-tool.php'; ?>
            </div>

            <div class="form-col-4">
                <div class="form-card">
                    <h3>Page Settings</h3>
                    <div class="form-group">
                        <label>Template</label>
                        <select name="template">
                            <option value="default" <?= $page['template'] === 'default' ? 'selected' : '' ?>>Default (With
                                Container)</option>
                            <option value="full-width" <?= $page['template'] === 'full-width' ? 'selected' : '' ?>>Full
                                Width (No Container)</option>
                            <option value="canvas" <?= $page['template'] === 'canvas' ? 'selected' : '' ?>>Canvas (Blank
                                Landing Page)</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Status</label><select name="status">
                            <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Published
                            </option>
                        </select></div>
                    <div class="form-group"><label>Featured Image</label>
                        <?php if ($page['featured_image']): ?><img
                            src="<?= APP_URL . '/' . h($page['featured_image'])?>"
                            style="width:100%; border-radius:8px; margin-bottom:10px;">
                        <?php endif; ?>
                        <div class="form-file-upload">
                            <input type="file" name="featured_image" id="input_featured_image_page" class="form-file-upload__input" accept="image/*">
                            <label for="input_featured_image_page" class="btn btn-outline btn-sm form-file-upload__label">Choose image</label>
                            <span class="form-file-upload__name" aria-live="polite"></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Update Page</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let aiBuffer = "";
    let currentPane = 'html-pane';

    document.addEventListener('DOMContentLoaded', () => {
        // Tab switching logic
        document.querySelectorAll('.editor-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const targetPane = tab.getAttribute('data-pane');
                if (currentPane === targetPane) return;

                // UI Switch
                document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.editor-pane').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(targetPane).classList.add('active');

                currentPane = targetPane;
            });
        });

        // AI logic
        document.getElementById('btn-ai-titlify')?.addEventListener('click', async function () {
            const btn = this;
            const originalHtml = btn.innerHTML;
            const t = document.getElementById('title').value;
            const body = new URLSearchParams();
            body.append('messages', JSON.stringify([{ role: 'user', content: 'Suggest 1 catchy page title: ' + t }]));
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const res = await fetch("https://ubsa.in/smartprogrammers/test/z.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: body.toString()
                });
                const data = await res.json();
                if (data && data.result) document.getElementById('title').value = data.result.trim().replace(/^"|"$/g, '');
            } catch (e) { }
            finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });

        document.getElementById('btn-ai-generate').addEventListener('click', async function () {
            const btn = this;
            const originalHtml = btn.innerHTML;
            const p = document.getElementById('ai-prompt').value.trim();
            if (!p) return;
            const status = document.getElementById('ai-status');
            status.style.display = 'block'; status.textContent = 'Writing...';
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Writing...';

            try {
                const body = new URLSearchParams();
                body.append('messages', JSON.stringify([{ role: 'user', content: 'Write a professional web page based on: ' + p + '. Use basic clean HTML (h2, p, ul, sections). No complex JS/CSS.' }]));
                const res = await fetch("https://ubsa.in/smartprogrammers/test/z.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: body.toString()
                });
                const data = await res.json();
                if (data && data.result) {
                    aiBuffer = data.result.replace(/```html/g, '').replace(/```/g, '').trim();
                    document.getElementById('ai-new-preview').textContent = aiBuffer;
                    document.getElementById('ai-preview-area').style.display = 'block';
                    status.textContent = '✅ Suggestion ready!';
                }
            } catch (e) { 
                status.textContent = '❌ AI Error'; 
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });

        document.getElementById('btn-ai-apply').addEventListener('click', () => {
            document.getElementById('raw-html-editor').value = aiBuffer;
            document.getElementById('ai-preview-area').style.display = 'none';
            document.getElementById('ai-status').textContent = '✅ Applied to HTML Source!';
        });

        document.getElementById('btn-ai-discard').addEventListener('click', () => { document.getElementById('ai-preview-area').style.display = 'none'; });

        if (typeof SEOOptimizer !== 'undefined') SEOOptimizer.update();
    });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>