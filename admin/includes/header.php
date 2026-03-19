<?php
/**
 * Admin Header
 * WordPress Inspired Classic Design
 */
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();
$_currentUser = currentUser();

// Fetch Permissions for Sidebar logic
$stmt = db()->prepare("SELECT permissions FROM users WHERE id = ?");
$stmt->execute([$_currentUser['id']]);
$u_data = $stmt->fetch();
$active_permissions = json_decode($u_data['permissions'] ?? '[]', true);
if (empty($active_permissions)) {
    if ($_currentUser['role'] === 'editor') {
        // Editors default to ONLY the Dashboard for maximum security
        $active_permissions = ['dashboard'];
    } else {
        // Administrators default to ALL master modules if no specific perms exist
        $active_permissions = array_column(array_filter(getAdminMenu(), fn($m) => isset($m['key'])), 'key');
    }
}

/**
 * Check if the current user has permission for a specific module
 */
function hasPermission($key) {
    global $_currentUser, $active_permissions;
    if ($_currentUser['role'] === 'admin') return true; // Admins ALWAYS have access
    return in_array($key, $active_permissions);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Dashboard')?> | Admin - <?= APP_NAME?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL?>/assets/css/admin.css?v=<?= time() ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
</head>
<body class="admin-body">
    <?php displayFlash(); ?>

    <!-- WordPress style Topbar -->
    <header class="admin-topbar">
        <div class="topbar-left" style="display: flex; align-items: center; gap: 12px;">
            <button id="sidebarToggle" class="action-btn" style="color:#fff; padding: 0 5px; font-size: 18px;"><i class="fas fa-bars"></i></button>
            <a href="<?= APP_URL?>/" target="_blank" style="color:#fff; text-decoration:none; font-size:14px; font-weight:700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-cube" style="font-size: 16px; color: #72aee6;"></i> <span class="topbar-hide-mobile"><?= APP_NAME?></span>
            </a>
        </div>
        <div class="topbar-right" style="display: flex; align-items: center; gap: 15px;">
            <a href="<?= APP_URL ?>/admin/profile.php" style="color:#fff; text-decoration:none; font-size:13px; font-weight:600; opacity: 0.9; display: flex; align-items: center; gap: 8px;">
                <div class="topbar-avatar" style="width: 20px; height: 20px; border-radius: 4px; background: rgba(255,255,255,0.15); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800;"><?= strtoupper(substr($_currentUser['name'], 0, 1)) ?></div>
                <span class="topbar-hide-mobile">Howdy, <?= h($_currentUser['name'])?></span>
            </a>
            <div class="topbar-divider" style="width: 1px; height: 16px; background: rgba(255,255,255,0.2);"></div>
            <a href="<?= APP_URL?>/logout.php" style="color:#fff; text-decoration:none; font-size:13px; font-weight:600; opacity: 0.7; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-sign-out-alt"></i> <span class="topbar-hide-mobile">Log Out</span>
            </a>
        </div>
    </header>

    <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

    <div style="display:flex; width:100%;">
        <!-- WordPress Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-mobile-header" style="display:none; justify-content: flex-end; padding: 10px;">
                <button id="sidebarClose" class="action-btn" style="color:#fff;"><i class="fas fa-times"></i></button>
            </div>
            <nav class="sidebar-nav">
                <?php 
                $admin_menu_master = getAdminMenu();
                foreach($admin_menu_master as $m): 
                    // Handle Separators
                    if ($m['type'] === 'separator') {
                        $style = isset($m['border']) ? 'height:10px; border-top: 1px solid rgba(255,255,255,0.05); margin: 5px 0;' : 'height:10px;';
                        echo '<div class="sidebar-separator" style="'.$style.'"></div>';
                        continue;
                    }

                    // Strict Visibility Check
                    if (!hasPermission($m['key'])) continue;
                    if (isset($m['is_admin_only']) && $m['is_admin_only'] && $_currentUser['role'] !== 'admin') continue;

                    // Handle Simple Links
                    if ($m['type'] === 'link'): 
                        $isActive = (($_SERVER['REQUEST_URI'] === APP_URL . $m['url']) || (strpos($m['url'], '/admin/'.strtolower($pageTitle ?? '')) !== false) || (strpos(strtolower($pageTitle ?? ''), strtolower($m['label'])) !== false)) ? 'active' : '';
                        
                        // Comments Badge Logic
                        $badgeHtml = '';
                        if (isset($m['badge']) && $m['badge'] && $m['key'] === 'comments') {
                            $pendingCommentsCount = 0;
                            try { $pendingCommentsCount = db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(); } catch (Exception $e) {}
                            if ($pendingCommentsCount > 0) $badgeHtml = '<span class="sidebar-badge-count">'.$pendingCommentsCount.'</span>';
                        }
                    ?>
                    <a href="<?= APP_URL . $m['url'] ?>" class="sidebar-link <?= $isActive ?>">
                        <i class="<?= $m['icon'] ?>"></i><span><?= $m['label'] ?></span>
                        <?= $badgeHtml ?>
                    </a>
                    <?php 
                    // Handle Groups (Submenus)
                    elseif ($m['type'] === 'group'): 
                        $isGroupActive = (strpos(strtolower($pageTitle ?? ''), strtolower($m['label'])) !== false || (isset($m['parent_url']) && strpos($_SERVER['REQUEST_URI'], $m['parent_url']) !== false)) ? 'active' : '';
                    ?>
                    <div class="sidebar-group <?= $isGroupActive ?>">
                        <a href="<?= APP_URL . $m['parent_url'] ?>" class="sidebar-link">
                            <i class="<?= $m['icon'] ?>"></i><span><?= $m['label'] ?></span>
                        </a>
                        <div class="sidebar-submenu">
                            <?php foreach($m['submenu'] as $sub): 
                                $isSubActive = (strpos($_SERVER['REQUEST_URI'], $sub['url']) !== false || (strtolower($pageTitle ?? '') === strtolower($sub['label']))) ? 'active' : '';
                            ?>
                            <a href="<?= APP_URL . $sub['url'] ?>" class="submenu-link <?= $isSubActive ?>"><?= $sub['label'] ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main">
            <div class="admin-content">