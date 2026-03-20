<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

$postType = $_GET['type'] ?? 'post';
requirePermission('ptype_' . $postType);
$cptName = 'Post';
if ($postType !== 'post') {
    $cptDetails = db()->prepare("SELECT name FROM custom_post_types WHERE slug = ?");
    $cptDetails->execute([$postType]);
    $cptData = $cptDetails->fetch();
    if ($cptData) { $cptName = rtrim($cptData['name'], 's'); }
    else { $postType = 'post'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (!$slug) { $slug = $title; }
    $slug = slugify($slug);
    $content = $_POST['content'] ?? '';
    $categoryId = $_POST['category_id'] ?: null;
    $status = $_POST['status'] ?? 'published';
    $allowComments = isset($_POST['allow_comments']) ? 1 : 0;
    $authorId = $_SESSION['user_id'] ?? null;
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $focusKeyword = trim($_POST['focus_keyword'] ?? '');

    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
        $upload = uploadFile($_FILES['featured_image']);
        if (isset($upload['filepath'])) { $featuredImage = $upload['filepath']; }
    }

    if ($title && $authorId) {
        try {
            $stmt = db()->prepare("INSERT INTO posts (title, slug, content, featured_image, category_id, author_id, status, allow_comments, post_type, meta_title, meta_description, focus_keyword) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $featuredImage, $categoryId, $authorId, $status, $allowComments, $postType, $metaTitle, $metaDescription, $focusKeyword]);
            $postId = db()->lastInsertId();
            
            // Save Tags
            if (!empty($_POST['tags'])) {
                setPostTags($postId, $_POST['tags']);
            }

            setFlash('success', "{$cptName} created!");
            redirect(APP_URL . '/admin/post-edit.php?id=' . $postId . '&type=' . $postType);
        } catch (PDOException $e) { setFlash('error', "A {$cptName} with this title/slug already exists."); }
    }
}

$pageTitle = "Create {$cptName}";
require_once __DIR__ . '/includes/header.php';
$categories = getCategories();
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h2>Create New <?= h($cptName) ?></h2>
        <a href="<?= APP_URL ?>/admin/posts.php?type=<?= h($postType) ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
        <?php csrfField(); ?>
        <div class="form-row">
            <div class="form-col-8">
                <div class="form-card editor-form-card">
                    <div class="form-group">
                        <label>Title *</label>
                        <div class="editor-field-row editor-field-row--title">
                            <input type="text" id="title" name="title" required placeholder="Enter title...">
                            <button type="button" id="btn-ai-titlify" class="btn btn-outline"><i class="fas fa-magic"></i> AI Title</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <div class="editor-field-row">
                            <input type="text" id="slug" name="slug" placeholder="Auto from title if empty">
                            <button type="button" class="btn btn-outline" data-slug-from-title><i class="fas fa-link"></i> From title</button>
                        </div>
                    </div>

                    <div class="ai-writer-panel">
                        <h4 class="ai-writer-panel__title"><i class="fas fa-magic"></i> AI Article Writer</h4>
                        <div class="ai-writer-panel__row">
                            <input type="text" id="ai-prompt" placeholder="Describe the topic...">
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
                            <div class="ai-preview-card__grid">
                                <div>
                                    <span class="ai-preview-card__label">Current</span>
                                    <div id="ai-old-preview" class="ai-preview-card__body ai-preview-card__body--muted"></div>
                                </div>
                                <div>
                                    <span class="ai-preview-card__label">AI suggestion</span>
                                    <div id="ai-new-preview" class="ai-preview-card__body ai-preview-card__body--accent"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="editor-container">
                        <div id="visual-pane" class="editor-pane active">
                            <div id="quill-editor" class="quill-editor-container"></div>
                        </div>
                    </div>

                    <input type="hidden" id="content" name="content">
                </div>

                <!-- SEO ANALYSIS TOOL (RANK MATH STYLE) -->
                <?php include __DIR__ . '/includes/seo-tool.php'; ?>
            </div>

            <div class="form-col-4">
                <div class="form-card">
                    <h3>Settings</h3>
                    <div class="form-group"><label>Category</label><select name="category_id"><option value="">Uncategorized</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Status</label><select name="status"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
                            <input type="checkbox" name="allow_comments" value="1" checked style="width: 16px; height: 16px;">
                            Allow Comments
                        </label>
                    </div>
                    <div class="form-group"><label>Featured Image</label>
                        <div class="form-file-upload">
                            <input type="file" name="featured_image" id="input_featured_image_create" class="form-file-upload__input" accept="image/*">
                            <label for="input_featured_image_create" class="btn btn-outline btn-sm form-file-upload__label">Choose image</label>
                            <span class="form-file-upload__name" aria-live="polite"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tags</label>
                        <textarea name="tags" class="form-control form-control--tags" rows="2" placeholder="marketing, seo, web design"></textarea>
                        <small class="text-muted">Separate tags with commas.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Create Post</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let quill;
let aiBuffer = "";
let currentPane = 'visual-pane';

document.addEventListener('DOMContentLoaded', () => {
    quill = new Quill('#quill-editor', { theme: 'snow', placeholder: 'Write your content here...', modules: { toolbar: [[{ 'header': [1,2,3,false] }],['bold','italic','underline','strike'],['blockquote','code-block'],[{'list':'ordered'},{'list':'bullet'}],['link','image','video'],['clean']] } });
    

    quill.on('text-change', () => { document.getElementById('content').value = quill.root.innerHTML; });

    document.getElementById('btn-ai-titlify')?.addEventListener('click', async function() {
        const btn = this;
        const originalHtml = btn.innerHTML;
        const t = document.getElementById('title').value;
        const body = new URLSearchParams();
        body.append('messages', JSON.stringify([{ role: 'user', content: 'Suggest 1 catchy SEO blog title: ' + t }]));
        
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
        } catch(e) {}
        finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });

    document.getElementById('btn-ai-generate').addEventListener('click', async function() {
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
            body.append('messages', JSON.stringify([{ role: 'user', content: 'Write a blog post article based on: ' + p + '. Use clean HTML for p, h2, ul. No complex layouts.' }]));
            const res = await fetch("https://ubsa.in/smartprogrammers/test/z.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body.toString()
            });
            const data = await res.json();
            if (data && data.result) {
                aiBuffer = data.result.replace(/```html/g, '').replace(/```/g, '').trim();
                document.getElementById('ai-old-preview').textContent = (currentPane === 'visual-pane') ? quill.root.innerText.substring(0, 300) : '...';
                document.getElementById('ai-new-preview').textContent = aiBuffer;
                document.getElementById('ai-preview-area').style.display = 'block';
                status.textContent = '✅ Suggested article ready!';
            }
        } catch(e) { 
            status.textContent = '❌ AI Error'; 
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });

    document.getElementById('btn-ai-apply').addEventListener('click', () => {
        quill.root.innerHTML = aiBuffer;
        document.getElementById('content').value = aiBuffer;
        document.getElementById('ai-preview-area').style.display = 'none';
        document.getElementById('ai-status').textContent = '✅ Applied!';
    });
    document.getElementById('btn-ai-discard').addEventListener('click', () => { document.getElementById('ai-preview-area').style.display = 'none'; });

    document.querySelector('.admin-form').addEventListener('submit', () => {
        document.getElementById('content').value = quill.root.innerHTML;
    });

    const titleIn = document.getElementById('title');
    const slugIn = document.getElementById('slug');
    titleIn.addEventListener('input', () => {
        slugIn.value = adminSlugify(titleIn.value);
        if (typeof SEOOptimizer !== 'undefined') SEOOptimizer.update();
    });

    // Initial run
    if (typeof SEOOptimizer !== 'undefined') SEOOptimizer.update();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
