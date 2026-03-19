<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    requireEditAccess();
    $action = $_POST['action'] ?? '';
    if ($action === 'upload' && isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/zip', 'application/x-zip-compressed'];
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'zip'];
        
        $upload = uploadFile($_FILES['file'], $allowedMimes, $allowedExts);
        if (isset($upload['filepath'])) {
            db()->prepare("INSERT INTO media (filename, filepath, mimetype, size, uploaded_by) VALUES (?, ?, ?, ?, ?)")
                ->execute([$_FILES['file']['name'], $upload['filepath'], $_FILES['file']['type'], $_FILES['file']['size'], $_currentUser['id']]);
            setFlash('success', 'File uploaded!');
        } else { 
            setFlash('error', $upload['error'] ?? 'Upload failed.'); 
        }
    } elseif ($action === 'delete') {
        requireAdmin();
        $id = intval($_POST['id'] ?? 0);
        $mediaItem = null;
        if ($id) {
            $stmt = db()->prepare("SELECT filepath FROM media WHERE id=?"); 
            $stmt->execute([$id]); 
            $mediaItem = $stmt->fetch();
            if ($mediaItem) {
                $filePath = BASE_PATH . '/' . $mediaItem['filepath'];
                if (file_exists($filePath)) unlink($filePath);
                db()->prepare("DELETE FROM media WHERE id=?")->execute([$id]);
                setFlash('success', 'File deleted.');
            }
        }
    }
    redirect(APP_URL . '/admin/media.php' . (isset($_GET['view']) ? '?view=' . $_GET['view'] : ''));
}

$viewMode = $_GET['view'] ?? 'grid';
$mediaList = getAllMedia();

$pageTitle = 'Media Library';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Media Library</h2>
            <p class="text-muted">Manage your images and documents</p>
        </div>
        <div class="header-actions">
            <div class="view-toggle-group">
                <a href="?view=grid" class="view-btn <?= $viewMode !== 'list' ? 'active' : '' ?>" title="Grid View"><i class="fas fa-th-large"></i></a>
                <a href="?view=list" class="view-btn <?= $viewMode === 'list' ? 'active' : '' ?>" title="List View"><i class="fas fa-list"></i></a>
            </div>
            <?php if (canEdit()): ?>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadCollapse').classList.toggle('active')">
                <i class="fas fa-plus"></i> Upload New
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (canEdit()): ?>
    <!-- Collapsible Upload Form -->
    <div id="uploadCollapse" class="upload-collapse-container">
        <div class="modern-card upload-card">
            <form action="" method="POST" enctype="multipart/form-data" class="upload-form-modern">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="upload">
                <div class="upload-area" id="dropZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div class="upload-text">
                        <strong>Choose a file</strong> or drag it here
                        <span>Support image, pdf, zip (Max 5MB)</span>
                    </div>
                    <input type="file" name="file" id="fileInput" accept="image/*,.pdf,.zip" required onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($mediaList)): ?>
        
        <?php if ($viewMode === 'list'): ?>
            <div class="modern-card no-padding overflow-hidden">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="80">Preview</th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mediaList as $m): ?>
                        <tr>
                            <td>
                                <div class="list-preview">
                                    <?php if(strpos($m['mimetype'], 'image') !== false): ?>
                                        <img src="<?= APP_URL . '/' . h($m['filepath']) ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-file-alt"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="file-name-cell">
                                    <strong><?= h($m['filename']) ?></strong>
                                    <small><?= h($m['filepath']) ?></small>
                                </div>
                            </td>
                            <td><span class="badge-outline"><?= round($m['size'] / 1024) ?> KB</span></td>
                            <td><span class="type-tag"><?= strtoupper(explode('/', $m['mimetype'])[1] ?? 'FILE') ?></span></td>
                            <td><small><?= date('M d, Y', strtotime($m['created_at'])) ?></small></td>
                            <td>
                                <div class="row-actions">
                                    <button onclick="copyToClipboard('<?= APP_URL . '/' . $m['filepath'] ?>')" class="action-btn" title="Copy URL"><i class="fas fa-link"></i></button>
                                    <?php if (canEdit()): ?>
                                    <form action="" method="POST" class="inline-form" onsubmit="return confirm('Permanently delete this file?')">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button class="action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="media-grid-modern">
                <?php foreach ($mediaList as $m): ?>
                <div class="media-card-modern">
                    <div class="media-card-preview">
                        <?php if(strpos($m['mimetype'], 'image') !== false): ?>
                            <img src="<?= APP_URL . '/' . h($m['filepath']) ?>" alt="<?= h($m['filename']) ?>">
                        <?php elseif(strpos($m['mimetype'], 'pdf') !== false): ?>
                            <div class="file-placeholder pdf-bg"><i class="fas fa-file-pdf"></i></div>
                        <?php elseif(strpos($m['mimetype'], 'zip') !== false): ?>
                            <div class="file-placeholder zip-bg"><i class="fas fa-file-archive"></i></div>
                        <?php else: ?>
                            <div class="file-placeholder"><i class="fas fa-file-alt"></i></div>
                        <?php endif; ?>
                        <div class="media-card-overlay">
                            <button onclick="copyToClipboard('<?= APP_URL . '/' . $m['filepath'] ?>')" class="overlay-btn"><i class="fas fa-link"></i></button>
                            <?php if (canEdit()): ?>
                            <form action="" method="POST" class="inline-form" onsubmit="return confirm('Delete this file?')">
                                <?php csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button class="overlay-btn delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="media-card-footer">
                        <span class="file-name" title="<?= h($m['filename']) ?>"><?= h($m['filename']) ?></span>
                        <span class="file-meta"><?= round($m['size'] / 1024) ?> KB &bull; <?= strtoupper(explode('/', $m['mimetype'])[1] ?? 'FILE') ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state-modern">
            <div class="empty-icon"><i class="fas fa-images"></i></div>
            <h3>Your library is empty</h3>
            <p>Upload images, documents, or reports to see them here.</p>
            <?php if (canEdit()): ?>
            <button class="btn btn-primary mt-3" onclick="document.getElementById('uploadCollapse').classList.add('active')">Upload First File</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Modern Media Styles */
.header-actions { display: flex; gap: 12px; align-items: center; }
.view-toggle-group { display: flex; background: #eee; padding: 4px; border-radius: 8px; }
.view-btn { padding: 6px 12px; border-radius: 6px; color: #666; transition: all 0.2s; }
.view-btn.active { background: #fff; color: var(--accent-primary); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

.upload-collapse-container { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; margin-bottom: 0; }
.upload-collapse-container.active { max-height: 300px; margin-bottom: 24px; }

.modern-card { background: #fff; border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
.modern-card.no-padding { padding: 0; }
.overflow-hidden { overflow: hidden; }

.upload-area { position: relative; border: 2px dashed #ccc; border-radius: 10px; padding: 30px; text-align: center; transition: all 0.2s; cursor: pointer; }
.upload-area:hover { border-color: var(--accent-primary); background: #f8f9ff; }
.upload-icon { font-size: 32px; color: var(--accent-primary); margin-bottom: 12px; }
.upload-text strong { display: block; color: #333; margin-bottom: 4px; }
.upload-text span { font-size: 12px; color: #888; }
#fileInput { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

/* Grid View */
.media-grid-modern { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
.media-card-modern { background: #fff; border-radius: 12px; border: 1px solid #eee; overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.media-card-modern:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
.media-card-preview { position: relative; height: 150px; background: #f5f5f5; overflow: hidden; }
.media-card-preview img { width: 100%; height: 100%; object-fit: cover; }
.file-placeholder { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #ccc; }
.file-placeholder.pdf-bg i { color: #e74c3c; }
.file-placeholder.zip-bg i { color: #f1c40f; }

.media-card-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; gap: 10px; opacity: 0; transition: opacity 0.2s; }
.media-card-modern:hover .media-card-overlay { opacity: 1; }
.overlay-btn { width: 36px; height: 36px; border-radius: 50%; background: #fff; border: none; font-size: 14px; cursor: pointer; transition: all 0.2s; color: #333; }
.overlay-btn:hover { transform: scale(1.1); background: var(--accent-primary); color: #fff; }
.overlay-btn.delete:hover { background: #ff4d4d; }

.media-card-footer { padding: 12px; }
.file-name { display: block; font-weight: 500; font-size: 14px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.file-meta { font-size: 11px; color: #999; text-transform: uppercase; margin-top: 4px; display: block; }

/* List View Specifics */
.list-preview { width: 40px; height: 40px; border-radius: 6px; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center; }
.list-preview img { width: 100%; height: 100%; object-fit: cover; }
.file-name-cell strong { display: block; color: #333; margin-bottom: 2px; }
.file-name-cell small { color: #999; font-size: 12px; }
.badge-outline { padding: 2px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 11px; color: #666; font-weight: 600; }
.type-tag { padding: 2px 6px; background: #e8f0fe; color: var(--accent-primary); border-radius: 4px; font-size: 10px; font-weight: 700; }

/* Empty State */
.empty-state-modern { text-align: center; padding: 80px 20px; background: #fff; border-radius: 20px; border: 2px dashed #ddd; }
.empty-icon { font-size: 60px; color: #eee; margin-bottom: 20px; }
.empty-state-modern h3 { font-size: 20px; color: #333; margin-bottom: 8px; }
.empty-state-modern p { color: #888; }
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('URL Copied to clipboard!');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
