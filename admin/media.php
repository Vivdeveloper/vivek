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
    $preserveView = $_POST['view_mode'] ?? $_GET['view'] ?? '';
    $preserveView = strtolower(trim((string) $preserveView));
    if (!in_array($preserveView, ['list', 'grid'], true)) {
        $preserveView = '';
    }
    $redir = APP_URL . '/admin/media.php';
    if ($preserveView !== '') {
        $redir .= '?view=' . rawurlencode($preserveView);
    }
    redirect($redir);
}

$rawView = strtolower(trim((string) ($_GET['view'] ?? 'grid')));
$viewMode = in_array($rawView, ['list', 'grid'], true) ? $rawView : 'grid';
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
            <div class="view-toggle-group" role="group" aria-label="Media view mode">
                <a href="<?= h(APP_URL) ?>/admin/media.php?view=grid" class="view-btn <?= $viewMode === 'grid' ? 'active' : '' ?>" title="Grid view" aria-pressed="<?= $viewMode === 'grid' ? 'true' : 'false' ?>"><i class="fas fa-th-large" aria-hidden="true"></i></a>
                <a href="<?= h(APP_URL) ?>/admin/media.php?view=list" class="view-btn <?= $viewMode === 'list' ? 'active' : '' ?>" title="List view" aria-pressed="<?= $viewMode === 'list' ? 'true' : 'false' ?>"><i class="fas fa-list" aria-hidden="true"></i></a>
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
            <form action="<?= h(APP_URL) ?>/admin/media.php<?= $viewMode === 'list' ? '?view=list' : ($viewMode === 'grid' ? '?view=grid' : '') ?>" method="POST" enctype="multipart/form-data" class="upload-form-modern">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="view_mode" value="<?= h($viewMode) ?>">
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
            <div class="modern-card no-padding overflow-hidden media-library-list-wrap">
                <table class="modern-table media-library-table">
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
                                    <form action="<?= h(APP_URL) ?>/admin/media.php<?= $viewMode === 'list' ? '?view=list' : '?view=grid' ?>" method="POST" class="inline-form" onsubmit="return confirm('Permanently delete this file?')">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="view_mode" value="<?= h($viewMode) ?>">
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
                            <form action="<?= h(APP_URL) ?>/admin/media.php<?= $viewMode === 'list' ? '?view=list' : '?view=grid' ?>" method="POST" class="inline-form" onsubmit="return confirm('Delete this file?')">
                                <?php csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="view_mode" value="<?= h($viewMode) ?>">
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


<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('URL Copied to clipboard!');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
