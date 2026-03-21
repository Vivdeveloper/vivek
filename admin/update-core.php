<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdminAreaAccess();

set_time_limit(300); // 5 minutes max

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_update'])) {
    if (!verifyCsrf()) die('Invalid CSRF token');

    $tmpDir = BASE_PATH . '/tmp';
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

    $zipFile = $tmpDir . '/latest.zip';
    $extractPath = $tmpDir . '/extract';

    // 1. Download Latest Main Branch from GitHub (Direct Master zip)
    $repoUrl = 'https://github.com/Vivdeveloper/vivek/archive/refs/heads/main.zip';
    
    $ch = curl_init();
    $fp = fopen($zipFile, "w+");
    curl_setopt($ch, CURLOPT_URL, $repoUrl);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_USERAGENT, 'VivFramework-Updater'); 
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($httpCode !== 200) {
        setFlash('error', 'Update failed: Unable to connect to GitHub. HTTP Code: ' . $httpCode);
        redirect(APP_URL . '/admin/index.php');
    }

    // 2. Extract Archive
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        if (!is_dir($extractPath)) mkdir($extractPath, 0755, true);
        $zip->extractTo($extractPath);
        $zip->close();

        // 3. Move files to root safely
        $wrapperDir = $extractPath . '/vivek-main'; // GitHub adds folder wrapper
        if (!is_dir($wrapperDir)) { 
            // Fallback if branch name changes
            $dirs = glob($extractPath . '/*' , GLOB_ONLYDIR);
            if (!empty($dirs)) $wrapperDir = $dirs[0];
        }

        if (is_dir($wrapperDir)) {
            // Setup Backup & Rollback Infrastructure
            $backupDir = $tmpDir . '/backup_rollback';
            if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

            // Clean previous backup loop
            function cleanDir($dir) { 
                if (!is_dir($dir)) return;
                $objects = scandir($dir); 
                foreach ($objects as $object) { 
                    if ($object != "." && $object != "..") { 
                        if (is_dir($dir."/".$object) && !is_link($dir."/".$object)) cleanDir($dir."/".$object);
                        else unlink($dir."/".$object); 
                    } 
                }
                rmdir($dir); 
            }
            cleanDir($backupDir); // Ensure fresh backup directory

            // Back up essential core components before risky overwriting
            function quickBackup($src, $dst) {
                if (is_dir($src)) {
                    // Skip volatile data
                    if (strpos($src, 'assets/uploads') !== false || strpos($src, 'tmp') !== false) return;
                    if (!is_dir($dst)) mkdir($dst, 0755, true);
                    $files = scandir($src);
                    foreach ($files as $file) {
                        if ($file != "." && $file != "..") quickBackup("$src/$file", "$dst/$file");
                    }
                } else if (file_exists($src)) {
                    copy($src, $dst);
                }
            }
            
            // Execute Backup
            quickBackup(BASE_PATH . '/admin', $backupDir . '/admin');
            quickBackup(BASE_PATH . '/includes', $backupDir . '/includes');
            $rootFiles = glob(BASE_PATH . '/*.php');
            if ($rootFiles) {
                foreach ($rootFiles as $rf) {
                    if (is_file($rf)) copy($rf, $backupDir . '/' . basename($rf));
                }
            }

            // Recursive Copy Function with strict error throwing
            function rcopy($src, $dst) {
                if (is_dir($src)) {
                    if (!is_dir($dst)) {
                        mkdir($dst, 0755, true);
                        @chmod($dst, 0755);
                    }
                    $files = scandir($src);
                    foreach ($files as $file) {
                        if ($file != "." && $file != "..") {
                            // SKIP CUSTOM FILES & DATABASES
                            if ($file === 'config.local.php' || $file === 'config.production.php' || $file === 'config.php') continue;
                            if (strpos($dst . '/' . $file, 'assets/uploads') !== false) continue;
                            if (strpos($file, '.git') !== false) continue;
                            
                            rcopy("$src/$file", "$dst/$file");
                        }
                    }
                } else if (file_exists($src)) {
                    if (!@copy($src, $dst)) throw new Exception("Write Permission Denied for file: " . basename($dst));
                    @chmod($dst, 0644); // Fix for Hostinger missing permissions
                }
            }
            
            try {
                // Execute Risky Update Overwrite
                rcopy($wrapperDir, BASE_PATH);
                setFlash('success', 'CMS Core updated successfully to the latest GitHub version!');
            } catch (Exception $e) {
                // CATASTROPHIC FAILURE DETECTED: EXECUTE ROLLBACK
                quickBackup($backupDir . '/admin', BASE_PATH . '/admin');
                quickBackup($backupDir . '/includes', BASE_PATH . '/includes');
                $rollbackRoots = glob($backupDir . '/*.php');
                if ($rollbackRoots) {
                    foreach ($rollbackRoots as $rrf) {
                        if (is_file($rrf)) @copy($rrf, BASE_PATH . '/' . basename($rrf));
                    }
                }
                setFlash('error', 'Update Failed: ' . $e->getMessage() . ' <br><b>Auto-Repair Triggered! System successfully rolled back to the previous version to prevent a crash.</b>');
            }

            // Clean up Temporary Files securely
            cleanDir($extractPath);
            cleanDir($backupDir);
            @unlink($zipFile);
            @unlink(BASE_PATH . '/tmp/update_check.json'); // Force fresh update check next time
        } else {
            setFlash('error', 'Update extraction failed: Invalid format received from GitHub.');
        }

    } else {
        setFlash('error', 'Update extraction failed. Could not open the ZIP archive.');
    }

    redirect(APP_URL . '/admin/index.php');
}

$pageTitle = 'Core Update Wizard';
require_once __DIR__ . '/includes/header.php';

$updateData = checkForUpdates();
$isUpdateAvailable = $updateData['available'] ?? false;
$targetVersion = $updateData['version'] ?? 'Unknown';
$changelog = $updateData['changelog'] ?? [];
$message = $updateData['message'] ?? 'No new updates available.';
?>
<div class="admin-page">
    <div class="admin-page-header">
        <h2>System Update Center</h2>
        <p class="text-muted">Manage your CMS version, check for updates, and view version history.</p>
    </div>

    <div class="row">
        <!-- Update Execution Panel -->
        <div class="col-md-6">
            <div class="admin-card" style="padding: 30px; border-top: 4px solid var(--wp-blue);">
                <div style="font-size: 3rem; color: var(--wp-blue); margin-bottom: 20px;">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <h3>Current Version: <span class="badge" style="background:#e2e8f0; color:#334155; padding:5px 10px; border-radius:20px; font-size:14px;"><?= h(APP_VERSION) ?></span></h3>
                
                <?php if ($isUpdateAvailable): ?>
                    <h4 style="color: #4f46e5; margin: 20px 0 10px;">🚀 Update Ready: Version <?= h($targetVersion) ?></h4>
                    <p style="color: var(--wp-text-secondary); font-size: 14px; margin-bottom: 30px; line-height:1.6;">
                        <strong><?= h($message) ?></strong><br><br>
                        This process securely downloads the newest code directly from your remote repository, 
                        overwrites the core files, and automatically syncs the database. Your uploaded media and configs are safely preserved.
                    </p>
                    
                    <form action="" method="POST">
                        <?php csrfField(); ?>
                        <input type="hidden" name="do_update" value="1">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px; border-radius: 5px; width:100%;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Downloading payload... do not close window'; this.style.pointerEvents='none';">
                            <i class="fas fa-cloud-download-alt"></i> Install Update Now
                        </button>
                    </form>
                <?php else: ?>
                    <div style="margin: 30px 0; padding: 20px; background: #ecfdf5; border-left: 4px solid #10b981; border-radius: 4px;">
                        <h4 style="color: #047857; margin-top:0;"><i class="fas fa-check-circle"></i> You are up to date!</h4>
                        <p style="color: #065f46; margin-bottom:0; font-size:14px;">Your system is running the latest available version.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Version History Panel -->
        <div class="col-md-6">
            <div class="admin-card" style="padding: 30px;">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-history text-muted"></i> Version History (Changelog)</h3>
                
                <?php if (!empty($changelog)): ?>
                    <ul style="list-style-type: none; padding-left: 0;">
                        <?php foreach($changelog as $idx => $log): ?>
                            <li style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; display: flex; gap: 15px; align-items: flex-start;">
                                <div style="color: #4f46e5; margin-top: 3px;"><i class="fas fa-check"></i></div>
                                <div style="color: #334155; font-size: 14px; line-height: 1.5;">
                                    <?= h($log) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No changelog data available for this version.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
