<?php
/**
 * Admin Header
 * WordPress Inspired Classic Design
 */
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();
$_currentUser = currentUser();
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
        <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
            <button id="sidebarToggle" class="action-btn" style="color:#fff; padding: 0 5px; font-size: 18px;"><i class="fas fa-bars"></i></button>
            <a href="<?= APP_URL?>/" target="_blank" style="color:#fff; text-decoration:none; font-size:13px; font-weight:600; opacity: 0.9;"><i class="fas fa-home"></i> <?= APP_NAME?></a>
        </div>
        <div class="topbar-right">
            <span>Howdy, <?= h($_currentUser['name'])?></span>
            <a href="<?= APP_URL?>/logout.php" style="margin-left:10px;">Log Out</a>
        </div>
    </header>

    <div style="display:flex; width:100%;">
        <!-- WordPress Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <nav class="sidebar-nav">
                <a href="<?= APP_URL?>/admin/" class="sidebar-link <?=($pageTitle ?? '') === 'Dashboard' ? 'active' : ''?>">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>
                
                <div class="sidebar-separator" style="height:10px;"></div>
                
                <!-- Posts Group -->
                <div class="sidebar-group <?= (strpos($pageTitle ?? '', 'Post') !== false || ($pageTitle ?? '') === 'Categories') ? 'active' : '' ?>">
                    <a href="<?= APP_URL?>/admin/posts.php" class="sidebar-link">
                        <i class="fas fa-thumbtack"></i><span>Posts</span>
                    </a>
                    <div class="sidebar-submenu">
                        <a href="<?= APP_URL?>/admin/posts.php" class="submenu-link <?= ($pageTitle ?? '') === 'All Posts' ? 'active' : '' ?>">All Posts</a>
                        <a href="<?= APP_URL?>/admin/post-create.php" class="submenu-link <?= ($pageTitle ?? '') === 'Create Post' ? 'active' : '' ?>">Add New</a>
                        <a href="<?= APP_URL?>/admin/categories.php" class="submenu-link <?= ($pageTitle ?? '') === 'Categories' ? 'active' : '' ?>">Categories</a>
                    </div>
                </div>

                <a href="<?= APP_URL?>/admin/media.php" class="sidebar-link <?= strpos($pageTitle ?? '', 'Media') !== false ? 'active' : ''?>">
                    <i class="fas fa-camera"></i><span>Media</span>
                </a>

                <!-- Pages Group -->
                <div class="sidebar-group <?= (strpos($pageTitle ?? '', 'Page') !== false) ? 'active' : '' ?>">
                    <a href="<?= APP_URL?>/admin/pages.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i><span>Pages</span>
                    </a>
                    <div class="sidebar-submenu">
                        <a href="<?= APP_URL?>/admin/pages.php" class="submenu-link <?= ($pageTitle ?? '') === 'All Pages' ? 'active' : '' ?>">All Pages</a>
                        <a href="<?= APP_URL?>/admin/page-create.php" class="submenu-link <?= ($pageTitle ?? '') === 'Create Page' ? 'active' : '' ?>">Add New</a>
                    </div>
                </div>
                
                <?php
                $pendingCommentsCount = 0;
                try { $pendingCommentsCount = db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(); } catch (Exception $e) {}
                ?>
                <a href="<?= APP_URL?>/admin/comments.php" class="sidebar-link <?= strpos($pageTitle ?? '', 'Comment') !== false ? 'active' : ''?>">
                    <i class="fas fa-comment"></i><span>Comments</span>
                    <?php if ($pendingCommentsCount > 0): ?>
                        <span class="sidebar-badge-count"><?= $pendingCommentsCount?></span>
                    <?php endif; ?>
                </a>

                <div class="sidebar-separator" style="height:10px; border-top: 1px solid rgba(255,255,255,0.05); margin: 5px 0;"></div>
                
                <!-- Design Group -->
                <div class="sidebar-group <?= (strpos($pageTitle ?? '', 'Design') !== false || strpos($pageTitle ?? '', 'Menu') !== false || ($pageTitle ?? '') === 'Theme Settings') ? 'active' : '' ?>">
                    <a href="<?= APP_URL?>/admin/theme-settings.php" class="sidebar-link">
                        <i class="fas fa-paint-brush"></i><span>Design</span>
                    </a>
                    <div class="sidebar-submenu">
                        <a href="<?= APP_URL?>/admin/theme-settings.php" class="submenu-link <?= ($pageTitle ?? '') === 'Design Settings' ? 'active' : '' ?>">Customizer</a>
                        <a href="<?= APP_URL?>/admin/menus.php" class="submenu-link <?= ($pageTitle ?? '') === 'Menus' ? 'active' : '' ?>">Menus</a>
                        <a href="<?= APP_URL?>/admin/theme-settings.php?tab=header" class="submenu-link">Header Design</a>
                        <a href="<?= APP_URL?>/admin/theme-settings.php?tab=footer" class="submenu-link">Footer Design</a>
                    </div>
                </div>

                 <?php if ($_currentUser['role'] === 'admin'): ?>
                <a href="<?= APP_URL?>/admin/users.php" class="sidebar-link <?= strpos($pageTitle ?? '', 'User') !== false ? 'active' : ''?>">
                    <i class="fas fa-users"></i><span>Users</span>
                </a>
                <?php endif; ?>
                
                <div class="sidebar-separator" style="height:10px;"></div>

                <a href="<?= APP_URL?>/admin/cpt-manager.php" class="sidebar-link <?= strpos($pageTitle ?? '', 'Custom Post') !== false ? 'active' : ''?>">
                    <i class="fas fa-tools"></i><span>CPT Manager</span>
                </a>
                <a href="<?= APP_URL?>/admin/custom-fields.php" class="sidebar-link <?= strpos($pageTitle ?? '', 'Custom Field') !== false ? 'active' : ''?>">
                    <i class="fas fa-database"></i><span>Custom Fields</span>
                </a>

                <div class="sidebar-separator" style="height:10px;"></div>

                <a href="<?= APP_URL?>/admin/theme-settings.php?tab=permalinks" class="sidebar-link <?= ($pageTitle ?? '') === 'Settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i><span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main">
            <div class="admin-content">