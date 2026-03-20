<?php
/**
 * Admin Header
 * WordPress Inspired Classic Design
 */
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
startSecureSession();
requireAdminAreaAccess();
$_currentUser = currentUser();

// Fetch Permissions for Sidebar logic
$stmt = db()->prepare("SELECT permissions FROM users WHERE id = ?");
$stmt->execute([$_currentUser['id']]);
$u_data = $stmt->fetch();
$active_permissions = json_decode($u_data['permissions'] ?? '[]', true);
if (empty($active_permissions)) {
    if ($_currentUser['role'] === 'editor') {
        // Editors get Dashboard and their personal Portal by default
        $active_permissions = ['dashboard', 'my_portal'];
    }
    else {
        // Administrators default to ALL master modules if no specific perms exist
        $active_permissions = array_column(array_filter(getAdminMenu(), fn($m) => isset($m['key'])), 'key');
    }
}

/**
 * Check if the current user has permission for a specific module
 */
function hasPermission($key)
{
    global $_currentUser, $active_permissions;
    if ($_currentUser['role'] === 'admin')
        return true; // Admins ALWAYS have access
    return in_array($key, $active_permissions);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= h($pageTitle ?? 'Dashboard')?> | Admin -
        <?= APP_NAME?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL?>/assets/css/admin.css?v=<?= time()?>">

    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script>window.APP_URL = "<?= APP_URL?>";</script>
</head>

<body class="admin-body">
    <?php displayFlash(); ?>

    <!-- WordPress style Topbar -->
    <header class="admin-topbar">
        <div class="topbar-left">
            <button id="sidebarToggle" class="action-btn" type="button" aria-label="Open menu"><i
                    class="fas fa-bars"></i></button>
            <a href="<?= APP_URL?>/" target="_blank" rel="noopener noreferrer" class="topbar-home-link">
                <i class="fas fa-cube topbar-logo-icon"></i> <span class="topbar-hide-mobile">
                    <?= APP_NAME?>
                </span>
            </a>
        </div>
        <div class="topbar-right">
            <a href="<?= APP_URL?>/admin/profile.php" class="topbar-profile-link">
                <div class="topbar-avatar">
                    <?= strtoupper(substr($_currentUser['name'], 0, 1))?>
                </div>
                <span class="topbar-hide-mobile">Howdy,
                    <?= h($_currentUser['name'])?>
                </span>
            </a>
            <div class="topbar-divider" aria-hidden="true"></div>
            <a href="<?= APP_URL?>/logout.php" class="topbar-logout-link">
                <i class="fas fa-sign-out-alt"></i> <span class="topbar-hide-mobile">Log Out</span>
            </a>
        </div>
    </header>

    <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-shell">
        <!-- WordPress Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-mobile-header">
                <button id="sidebarClose" class="action-btn" type="button" aria-label="Close menu"><i class="fas fa-times"></i></button>
            </div>
            <nav class="sidebar-nav">
                <?php
$admin_menu_master = getAdminMenu();
foreach ($admin_menu_master as $m):
    // Handle Separators
    if ($m['type'] === 'separator') {
        $sepClass = 'sidebar-separator' . (isset($m['border']) ? ' sidebar-separator--border' : '');
        echo '<div class="' . $sepClass . '"></div>';
        continue;
    }

    // Strict Visibility Check
    if (!hasPermission($m['key']))
        continue;
    if (isset($m['is_admin_only']) && $m['is_admin_only'] && $_currentUser['role'] !== 'admin')
        continue;

    // Handle Simple Links
    if ($m['type'] === 'link'):
        $isActive = (($_SERVER['REQUEST_URI'] === APP_URL . $m['url']) || (strpos($m['url'], '/admin/' . strtolower($pageTitle ?? '')) !== false) || (strpos(strtolower($pageTitle ?? ''), strtolower($m['label'])) !== false)) ? 'active' : '';

        // Comments Badge Logic
        $badgeHtml = '';
        if (isset($m['badge']) && $m['badge'] && $m['key'] === 'comments') {
            $pendingCommentsCount = 0;
            try {
                $pendingCommentsCount = db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
            }
            catch (Exception $e) {
            }
            if ($pendingCommentsCount > 0)
                $badgeHtml = '<span class="sidebar-badge-count">' . $pendingCommentsCount . '</span>';
        }
?>
                <a href="<?= APP_URL . $m['url']?>" class="sidebar-link <?= $isActive?>">
                    <i class="<?= $m['icon']?>"></i><span>
                        <?= $m['label']?>
                    </span>
                    <?= $badgeHtml?>
                </a>
                <?php
    // Handle Groups (Submenus)
    elseif ($m['type'] === 'group'):
        $isGroupActive = (strpos(strtolower($pageTitle ?? ''), strtolower($m['label'])) !== false || (isset($m['parent_url']) && strpos($_SERVER['REQUEST_URI'], $m['parent_url']) !== false)) ? 'active' : '';
?>
                <div class="sidebar-group <?= $isGroupActive?>">
                    <a href="<?= APP_URL . $m['parent_url']?>" class="sidebar-link">
                        <i class="<?= $m['icon']?>"></i><span>
                            <?= $m['label']?>
                        </span>
                    </a>
                    <div class="sidebar-submenu">
                        <?php foreach ($m['submenu'] as $sub):
            $isSubActive = (strpos($_SERVER['REQUEST_URI'], $sub['url']) !== false || (strtolower($pageTitle ?? '') === strtolower($sub['label']))) ? 'active' : '';
?>
                        <a href="<?= APP_URL . $sub['url']?>" class="submenu-link <?= $isSubActive?>">
                            <?= $sub['label']?>
                        </a>
                        <?php
        endforeach; ?>
                    </div>
                </div>
                <?php
    endif; ?>
                <?php
endforeach; ?>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main">
            <div class="admin-content">