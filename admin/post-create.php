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

    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
        $upload = uploadFile($_FILES['featured_image']);
        if (isset($upload['filepath'])) { $featuredImage = $upload['filepath']; }
    }

    if ($title && $authorId) {
        try {
            $stmt = db()->prepare("INSERT INTO posts (title, slug, content, featured_image, category_id, author_id, status, allow_comments, post_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $featuredImage, $categoryId, $authorId, $status, $allowComments, $postType]);
            $postId = db()->lastInsertId();
            setFlash('success', "{$cptName} created!");
            redirect(APP_URL . '/admin/post-edit.php?id=' . $postId . '&type=' . $postType);
        } catch (PDOException $e) { setFlash('error', "A {$cptName} with this title/slug already exists."); }
    }
}

$pageTitle = "Create {$cptName}";
require_once __DIR__ . '/includes/header.php';
$categories = getCategories();
?>
<style>
    .editor-container { position: relative; margin-bottom: 20px; }
    .editor-tabs { display: flex; gap: 5px; margin-bottom: -1px; }
    .editor-tab { padding: 10px 20px; background: #fdfdfd; border: 1px solid #dce4f5; border-bottom: none; border-radius: 8px 8px 0 0; cursor: pointer; font-size: 13px; font-weight: 500; color: #666; transition: all 0.2s; }
    .editor-tab.active { background: #fff; color: #2271b1; border-bottom: 2px solid #2271b1; }
    .editor-pane { display: none; background: #fff; border: 1px solid #dce4f5; border-radius: 0 8px 8px 8px; padding: 1px; }
    .editor-pane.active { display: block; }
    #raw-html-editor { width: 100%; height: 500px; border: none; padding: 15px; font-family: 'Courier New', monospace; font-size: 14px; color: #1d2327; background: #fafafa; outline: none; resize: vertical; }
</style>

<div class="admin-page">
    <div class="admin-page-header">
        <h2>Create New <?= h($cptName) ?></h2>
        <a href="<?= APP_URL ?>/admin/posts.php?type=<?= h($postType) ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
        <?php csrfField(); ?>
        <div class="form-row">
            <div class="form-col-8">
                <div class="form-card">
                    <div class="form-group">
                        <label>Title *</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="title" name="title" required placeholder="Enter title..." style="font-size:18px; flex:1;">
                            <button type="button" id="btn-ai-titlify" class="btn btn-outline" style="white-space:nowrap;"><i class="fas fa-magic"></i> AI Title</button>
                        </div>
                    </div>
                    <div class="form-group"><label>Slug</label><input type="text" id="slug" name="slug"></div>
                    
                    <!-- AI Article Writer Panel -->
                    <div class="ai-generator-panel" style="background: linear-gradient(135deg, #f8faff, #f0f4ff); border: 1px solid #dce4f5; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #3b5998; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-magic"></i> AI Article Writer
                        </h4>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="ai-prompt" placeholder="Describe the topic..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;">
                            <button type="button" id="btn-ai-generate" class="btn btn-primary" style="white-space: nowrap;"><i class="fas fa-pen-nib"></i> Write Content</button>
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
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 10px; max-height: 250px; overflow-y: auto;">
                                <div><small style="text-transform: uppercase; font-size: 10px; font-weight: 700;">Old</small><div id="ai-old-preview" style="font-size: 11px; white-space: pre-wrap; background: #f6f7f7; padding: 8px;"></div></div>
                                <div><small style="text-transform: uppercase; font-size: 10px; font-weight: 700;">AI Suggestion</small><div id="ai-new-preview" style="font-size: 11px; white-space: pre-wrap; background: #ebf5ff; padding: 8px;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Editor Container -->
                    <div class="editor-container">
                        <div id="visual-pane" class="editor-pane active">
                            <div id="quill-editor" class="quill-editor-container" style="height:600px; border:none;"></div>
                        </div>
                    </div>

                    <input type="hidden" id="content" name="content">
                </div>
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
                    <div class="form-group"><label>Featured Image</label><input type="file" name="featured_image"></div>
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
        const t = document.getElementById('title').value;
        const body = new URLSearchParams();
        body.append('messages', JSON.stringify([{ role: 'user', content: 'Suggest 1 catchy SEO blog title: ' + t }]));
        try {
            const res = await fetch("https://ubsa.in/smartprogrammers/test/z.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body.toString()
            });
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
        } catch(e) { status.textContent = '❌ AI Error'; }
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
        slugIn.value = titleIn.value.toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '');
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
