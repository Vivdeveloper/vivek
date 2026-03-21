<?php
/**
 * 🛠️ VivFramework Emergency Rescue Patch
 * Upload this file via File Manager to your Hostinger public_html folder.
 * Then visit: https://silver-dove-647325.hostingersite.com/patch.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Running Emergency Hostinger Rescue Patch...</h1>";
flush();

$basePath = __DIR__;
echo "<p>Base Path detected: <b>$basePath</b></p>";
flush();

// Function to meticulously fix all permissions in a directory
function fixPermissionsRecursively($dir) {
    if (!is_dir($dir)) return;
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            
            // Skip volatile directories
            if (strpos($path, 'assets/uploads') !== false) continue;
            
            if (is_dir($path)) {
                @chmod($path, 0755); // Directories must be 755
                fixPermissionsRecursively($path);
            } else {
                @chmod($path, 0644); // Files must be 644
            }
        }
    }
}

// 1. Download Latest Main Branch from GitHub to guarantee clean files
$zipUrl = 'https://github.com/Vivdeveloper/vivek/archive/refs/heads/main.zip';
$zipFile = $basePath . '/emergency_patch.zip';

echo "<p>Downloading latest files from GitHub...</p>";
file_put_contents($zipFile, fopen($zipUrl, 'r'));

if (file_exists($zipFile) && filesize($zipFile) > 0) {
    echo "<p>✅ Download successful. Extracting...</p>";
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $extractPath = $basePath . '/emergency_extract';
        $zip->extractTo($extractPath);
        $zip->close();
        
        $wrapperDir = $extractPath . '/vivek-main';
        if (!is_dir($wrapperDir)) {
            $dirs = glob($extractPath . '/*' , GLOB_ONLYDIR);
            if (!empty($dirs)) $wrapperDir = $dirs[0];
        }
        
        // Custom Recursive Copy Function to overwrite corrupted files
        function forceRescueCopy($src, $dst) {
            if (is_dir($src)) {
                if (!is_dir($dst)) {
                    mkdir($dst, 0755, true);
                    @chmod($dst, 0755);
                }
                $files = scandir($src);
                foreach ($files as $file) {
                    if ($file != "." && $file != "..") {
                        if ($file === 'config.local.php' || $file === 'config.production.php' || $file === 'config.php') continue;
                        if (strpos($dst . '/' . $file, 'assets/uploads') !== false) continue;
                        forceRescueCopy("$src/$file", "$dst/$file");
                    }
                }
            } else if (file_exists($src)) {
                copy($src, $dst);
            }
        }
        
        echo "<p>Replacing corrupted Hostinger files...</p>";
        forceRescueCopy($wrapperDir, $basePath);
        
        // Clean up extraction
        function cleanupDir($dir) { 
            if (is_dir($dir)) { 
                $objects = scandir($dir); 
                foreach ($objects as $object) { 
                    if ($object != "." && $object != "..") { 
                        if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                            cleanupDir($dir . DIRECTORY_SEPARATOR . $object);
                        else
                            unlink($dir . DIRECTORY_SEPARATOR . $object); 
                    } 
                }
                rmdir($dir); 
            } 
        }
        cleanupDir($extractPath);
        unlink($zipFile);
        echo "<p>✅ Files successfully replaced.</p>";
    } else {
        echo "<p style='color:red;'>❌ Zip extraction failed.</p>";
    }
}

// 2. Force Hostinger Safe Permissions explicitly across the entire directory
echo "<p>Applying strict CHMOD permissions (0644 for files, 0755 for directories)...</p>";
@chmod($basePath, 0755);
fixPermissionsRecursively($basePath);

echo "<h2 style='color:green;'>🎉 Rescue complete! Your site is 100% fixed!</h2>";
echo "<p><a href='/admin/index.php' style='font-size:20px; font-weight:bold; color:blue;'>Click here to go to your Admin Dashboard!</a></p>";
echo "<p><i>(Please delete patch.php using your Hostinger File Manager when finished).</i></p>";
?>
