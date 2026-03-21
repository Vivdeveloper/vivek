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
            // Recursive Copy Function
            function rcopy($src, $dst) {
                if (is_dir($src)) {
                    if (!is_dir($dst)) mkdir($dst, 0755, true);
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
                    copy($src, $dst);
                }
            }
            
            // Execute Copy Engine
            rcopy($wrapperDir, BASE_PATH);

            // 4. Clean up Temporary Files securely (Windows + Unix fallback)
            function rrmdir($dir) { 
                if (is_dir($dir)) { 
                    $objects = scandir($dir); 
                    foreach ($objects as $object) { 
                        if ($object != "." && $object != "..") { 
                            if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                                rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                            else
                                unlink($dir. DIRECTORY_SEPARATOR .$object); 
                        } 
                    }
                    rmdir($dir); 
                } 
            }
            rrmdir($extractPath);
            @unlink($zipFile);
            @unlink(BASE_PATH . '/tmp/update_check.json'); // Force fresh update check next time

            setFlash('success', 'CMS Core updated successfully to the latest GitHub version!');
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
?>
<div class="admin-page">
    <div class="admin-page-header">
        <h2>One-Click Software Update</h2>
    </div>

    <div class="admin-card" style="max-width: 600px; padding: 30px;">
        <div style="text-align: center; font-size: 3rem; color: var(--vf-accent); margin-bottom: 20px;">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <h3 style="text-align: center; margin-bottom: 15px;">Update Ready to Install</h3>
        
        <p style="color: var(--wp-text-secondary); text-align: center; font-size: 15px; margin-bottom: 30px;">
            This process securely downloads the newest code directly from your remote repository, 
            overwrites the core files, and automatically syncs the database.
            <br><br>
            <strong>Safe Actions:</strong> Your uploaded media, personal configs, and custom content are securely skipped during the overwrite.
        </p>
        
        <form action="" method="POST" style="text-align: center;">
            <?php csrfField(); ?>
            <input type="hidden" name="do_update" value="1">
            <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px; border-radius: 5px;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Downloading payload... do not close window'; this.style.pointerEvents='none';">
                <i class="fas fa-cloud-download-alt"></i> Initialize Direct Update
            </button>
            <br><br>
            <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-outline">Cancel</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
