<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

// ============================================
// 🔒 AUTHENTICATION & ACCESS FUNCTIONS
// ============================================

/**
 * Returns a globally synced Admin Sidebar Master Menu
 * Used for both generating the Sidebar and the Permission UI
 */
function getAdminMenu() {
    $menu = [
        ['type' => 'link', 'key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => '/admin/'],
        ['type' => 'separator'],
    ];

    // Dynamic Post Types Injection based on Database
    // We treat 'post' as a special reserved type for the main blog
    $post_types = db()->query("SELECT * FROM custom_post_types ORDER BY name ASC")->fetchAll();
    
    // Always include the standard 'post' type if it exists in the core logic
    // Usually the 'post' type is the first one
    $menu[] = ['type' => 'group', 'key' => 'ptype_post', 'label' => 'Blog Posts', 'icon' => 'fas fa-thumbtack', 'parent_url' => '/admin/posts.php', 'submenu' => [
        ['label' => 'All Posts', 'url' => '/admin/posts.php'],
        ['label' => 'Add New', 'url' => '/admin/post-create.php'],
        ['label' => 'Categories', 'url' => '/admin/categories.php'],
        ['label' => 'Tags', 'url' => '/admin/tags.php']
    ]];

    foreach ($post_types as $pt) {
        if ($pt['slug'] === 'post') continue; // Avoid duplication
        
        $menu[] = ['type' => 'group', 'key' => 'ptype_' . $pt['slug'], 'label' => h($pt['name']), 'icon' => h($pt['icon'] ?: 'fas fa-thumbtack'), 'parent_url' => '/admin/posts.php?type=' . h($pt['slug']), 'submenu' => [
            ['label' => 'All ' . h($pt['name']), 'url' => '/admin/posts.php?type=' . h($pt['slug'])],
            ['label' => 'Add New', 'url' => '/admin/post-create.php?type=' . h($pt['slug'])]
        ]];
    }

    $menu = array_merge($menu, [
        ['type' => 'link', 'key' => 'media', 'label' => 'Media', 'icon' => 'fas fa-camera', 'url' => '/admin/media.php'],
        ['type' => 'group', 'key' => 'pages', 'label' => 'Pages', 'icon' => 'fas fa-file-alt', 'parent_url' => '/admin/pages.php', 'submenu' => [
            ['label' => 'All Pages', 'url' => '/admin/pages.php'],
            ['label' => 'Add New', 'url' => '/admin/page-create.php']
        ]],
        ['type' => 'link', 'key' => 'comments', 'label' => 'Comments', 'icon' => 'fas fa-comment', 'url' => '/admin/comments.php', 'badge' => true],
        ['type' => 'separator', 'border' => true],
        ['type' => 'group', 'key' => 'design', 'label' => 'Theme Setting', 'icon' => 'fas fa-paint-brush', 'parent_url' => '/admin/theme-settings.php', 'submenu' => [
            ['label' => 'Theme Setting', 'url' => '/admin/theme-settings.php'],
            ['label' => 'Menus', 'url' => '/admin/menus.php'],
            ['label' => 'Header Design', 'url' => '/admin/theme-settings.php?tab=header'],
            ['label' => 'Footer Design', 'url' => '/admin/theme-settings.php?tab=footer']
        ]],
        ['type' => 'link', 'key' => 'floating_cta', 'label' => 'Floating CTA', 'icon' => 'fas fa-comments', 'url' => '/admin/cta-buttons.php'],
        ['type' => 'link', 'key' => 'custom_code', 'label' => 'Custom Code', 'icon' => 'fas fa-code', 'url' => '/admin/custom-code.php'],
        ['type' => 'link', 'key' => 'users', 'label' => 'Users', 'icon' => 'fas fa-users', 'url' => '/admin/users.php', 'is_admin_only' => true],
        ['type' => 'separator'],
        ['type' => 'link', 'key' => 'cpt_manager', 'label' => 'CPT Manager', 'icon' => 'fas fa-tools', 'url' => '/admin/cpt-manager.php'],
        ['type' => 'link', 'key' => 'custom_fields', 'label' => 'Custom Fields', 'icon' => 'fas fa-database', 'url' => '/admin/custom-fields.php'],
        ['type' => 'separator'],
        ['type' => 'link', 'key' => 'settings', 'label' => 'Settings', 'icon' => 'fas fa-cog', 'url' => '/admin/theme-settings.php?tab=permalinks'],
        ['type' => 'link', 'key' => 'import_export', 'label' => 'Import/Export', 'icon' => 'fas fa-exchange-alt', 'url' => '/admin/import-export.php', 'is_admin_only' => true]
    ]);

    return $menu;
}

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => 86400, // 1 day
            'path' => '/',
            'domain' => '', 
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fresh checks are moved below to avoid duplication and use DB verification

// isEditorOrAdmin is superseded by canEdit() and hasAccess()

/**
 * Enhanced authorization: Check if user is either Admin OR has specific permission
 */
function hasAccess($key) {
    if (!isLoggedIn()) return false;
    if (isAdmin()) return true; // Administrator always has access
    
    // Fetch fresh permissions from DB 
    $stmt = db()->prepare("SELECT permissions FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $perms = json_decode($stmt->fetchColumn() ?? '[]', true);
    
    // If no specific perms set, default to none for security (admins are handled above)
    return in_array($key, $perms);
}

function requirePermission($key) {
    if (!hasAccess($key)) {
        setFlash('error', 'Access denied. You do not have permission for this module.');
        redirect(APP_URL . '/admin/');
    }
}

function isAdmin() {
    if (!isLoggedIn()) return false;
    $stmt = db()->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    return $role === 'admin';
}

function canEdit() {
    if (!isLoggedIn()) return false;
    
    // Fetch fresh role from DB to avoid staleness
    $stmt = db()->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    
    return in_array($role, ['admin', 'editor']);
}

function requireEditAccess() {
    if (!canEdit()) {
        setFlash('error', 'Access denied. Editorial privileges required.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (APP_URL . '/admin/')));
        exit;
    }
}

function currentUser() {
    if (!isLoggedIn()) return null;
    
    // Fetch fresh user data from DB to avoid staleness (e.g. role changes)
    $stmt = db()->prepare("SELECT id, name, email, role, is_blocked, permissions FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to access this page.');
        redirect(APP_URL . '/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Admin privileges required.');
        redirect(APP_URL . '/login.php');
    }
}

function requireEditorOrAdmin() {
    requireLogin();
    if (!canEdit()) {
        setFlash('error', 'Access denied.');
        redirect(APP_URL . '/login.php');
    }
}

// ============================================
// 💬 FLASH MESSAGES
// ============================================

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        echo '<div class="flash-message flash-' . h($flash['type']) . '" id="flashMessage">';
        echo '<div class="flash-content">';
        echo '<i class="fas ' . $icon . '"></i>';
        echo '<span>' . h($flash['message']) . '</span>';
        echo '</div>';
        echo '<button onclick="this.parentElement.remove()" class="flash-close">&times;</button>';
        echo '</div>';
    }
}

// ============================================
// 🔧 UTILITY FUNCTIONS
// ============================================

/**
 * Escape HTML output (prevent XSS)
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Generate clean permalink for a blog post
 * e.g. https://yourdomain.com/my-blog-post
 */
function postUrl($slug, $category_slug = null, $id = null) {
    if (getSetting('permalink_structure', 'post_name') === 'plain' && $id) {
        return APP_URL . '/post.php?p=' . $id;
    }
    
    $structure = getSetting('permalink_structure', 'post_name');
    if ($structure === 'category_post_name' && !empty($category_slug)) {
        return APP_URL . '/' . $category_slug . '/' . $slug;
    }
    
    return APP_URL . '/' . $slug;
}

/**
 * Generate clean permalink for a CMS page
 * e.g. https://yourdomain.com/about
 */
function pageUrl($slug, $id = null) {
    if (getSetting('permalink_structure', 'post_name') === 'plain' && $id) {
        return APP_URL . '/page.php?id=' . $id;
    }
    return APP_URL . '/' . $slug;
}

/**
 * Generate clean permalink for a category
 * e.g. https://yourdomain.com/category/seo-tips
 */
function categoryUrl($slug) {
    return APP_URL . '/category/' . h($slug);
}

/**
 * Generate clean permalink for a tag
 * e.g. https://yourdomain.com/tag/marketing
 */
function tagUrl($slug) {
    return APP_URL . '/tag/' . h($slug);
}

/**
 * Generate slug from text
 */
function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Truncate text
 */
function truncate($text, $length = 150) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format long date
 */
function formatDateLong($date) {
    return date('l, F d, Y', strtotime($date));
}

/**
 * Generate CSRF token
 */
function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output CSRF hidden field
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') return true;
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Force regenerate CSRF token (for login/logout)
 */
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Handle file upload
 */
function uploadFile($file, $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed (Code: ' . $file['error'] . ')'];
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['error' => 'File too large (max ' . round(MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)'];
    }
    
    // Secure Mime-Type check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($realMime, $allowedMimes)) {
        return ['error' => 'Invalid file type: ' . $realMime];
    }
    
    // Extension check
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['error' => 'Invalid file extension: .' . $ext];
    }
    
    // Generate secure filename
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $filepath = UPLOAD_PATH . $filename;
    
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => 'assets/uploads/' . $filename];
    }
    
    return ['error' => 'Failed to save file securely'];
}

/**
 * Pagination helper
 */
function paginate($totalItems, $currentPage, $perPage = POSTS_PER_PAGE) {
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset
    ];
}

// ============================================
// 📊 DATA FUNCTIONS
// ============================================

// --- POSTS ---
function getPosts($limit = POSTS_PER_PAGE, $offset = 0, $status = 'published') {
    $stmt = db()->prepare("
        SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = ?
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$status, $limit, $offset]);
    return $stmt->fetchAll();
}

function getAllPosts($limit = 50) {
    $stmt = db()->prepare("
        SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getPostBySlug($slug) {
    $canSeeDrafts = canEdit();
    $statusFilter = $canSeeDrafts ? "" : " AND p.status = 'published'";
    
    $stmt = db()->prepare("
        SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.slug = ? " . $statusFilter . "
    ");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getPostById($id) {
    $stmt = db()->prepare("
        SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPostsByCategory($categorySlug, $limit = POSTS_PER_PAGE, $offset = 0) {
    $stmt = db()->prepare("
        SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE c.slug = ? AND p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$categorySlug, $limit, $offset]);
    return $stmt->fetchAll();
}

function searchPosts($query, $limit = POSTS_PER_PAGE, $offset = 0) {
    $search = "%$query%";
    $stmt = db()->prepare("
        SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE (p.title LIKE ? OR p.content LIKE ?) AND p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$search, $search, $limit, $offset]);
    return $stmt->fetchAll();
}

function getRecentPosts($limit = 5) {
    $stmt = db()->prepare("
        SELECT p.*, u.name as author_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function countPosts($status = null) {
    if ($status) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE status = ?");
        $stmt->execute([$status]);
    } else {
        $stmt = db()->query("SELECT COUNT(*) FROM posts");
    }
    return $stmt->fetchColumn();
}

function countPostsByCategory($categorySlug) {
    $stmt = db()->prepare("
        SELECT COUNT(*) FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE c.slug = ? AND p.status = 'published'
    ");
    $stmt->execute([$categorySlug]);
    return $stmt->fetchColumn();
}

function countSearchPosts($query) {
    $search = "%$query%";
    $stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE (title LIKE ? OR content LIKE ?) AND status = 'published'");
    $stmt->execute([$search, $search]);
    return $stmt->fetchColumn();
}

// --- CATEGORIES ---
function getCategories() {
    return db()->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}

function getCategoriesWithCount() {
    return db()->query("
        SELECT c.*, COUNT(p.id) as post_count
        FROM categories c
        LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
        GROUP BY c.id
        ORDER BY c.name ASC
    ")->fetchAll();
}

function getCategoryBySlug($slug) {
    $stmt = db()->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getCategoryById($id) {
    $stmt = db()->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// --- TAGS ---
function getTags() {
    return db()->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll();
}

function getTagsWithCount() {
    return db()->query("
        SELECT t.*, COUNT(pt.post_id) as post_count
        FROM tags t
        LEFT JOIN post_tags pt ON t.id = pt.tag_id
        GROUP BY t.id
        ORDER BY t.name ASC
    ")->fetchAll();
}

function getPostTags($postId) {
    $stmt = db()->prepare("
        SELECT t.* 
        FROM tags t
        JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = ?
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function setPostTags($postId, $tagsInput) {
    // tagsInput can be an array of IDs or a comma-separated string of names
    db()->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$postId]);
    
    if (is_string($tagsInput)) {
        $tagNames = array_map('trim', explode(',', $tagsInput));
        foreach ($tagNames as $name) {
            if (empty($name)) continue;
            
            // Get or create tag
            $slug = slugify($name);
            $stmt = db()->prepare("SELECT id FROM tags WHERE slug = ?");
            $stmt->execute([$slug]);
            $tagId = $stmt->fetchColumn();
            
            if (!$tagId) {
                $stmt = db()->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
                $tagId = db()->lastInsertId();
            }
            
            db()->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$postId, $tagId]);
        }
    } elseif (is_array($tagsInput)) {
        foreach ($tagsInput as $tagId) {
            db()->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$postId, (int)$tagId]);
        }
    }
}

function getTagBySlug($slug) {
    $stmt = db()->prepare("SELECT * FROM tags WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function countPostsByTag($slug) {
    $stmt = db()->prepare("
        SELECT COUNT(DISTINCT p.id) 
        FROM posts p
        JOIN post_tags pt ON p.id = pt.post_id
        JOIN tags t ON pt.tag_id = t.id
        WHERE t.slug = ? AND p.status = 'published'
    ");
    $stmt->execute([$slug]);
    return $stmt->fetchColumn();
}

function getPostsByTag($slug, $limit = 9, $offset = 0) {
    $limit = (int)$limit;
    $offset = (int)$offset;
    $stmt = db()->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        JOIN post_tags pt ON p.id = pt.post_id
        JOIN tags t ON pt.tag_id = t.id
        WHERE t.slug = ? AND p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute([$slug]);
    return $stmt->fetchAll();
}

// --- COMMENTS ---
function getCommentsByPost($postId) {
    $stmt = db()->prepare("
        SELECT c.*, u.name as user_name
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ? AND c.status = 'approved'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function getAllComments($limit = 50, $status = null) {
    $sql = "
        SELECT c.*, p.title as post_title, p.slug as post_slug, cat.slug as post_category_slug, u.name as user_name
        FROM comments c
        LEFT JOIN posts p ON c.post_id = p.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        LEFT JOIN users u ON c.user_id = u.id
    ";
    if ($status) {
        $sql .= " WHERE c.status = ?";
        $stmt = db()->prepare($sql . " ORDER BY c.created_at DESC LIMIT " . (int)$limit);
        $stmt->execute([$status]);
    } else {
        $stmt = db()->prepare($sql . " ORDER BY c.created_at DESC LIMIT " . (int)$limit);
        $stmt->execute([]);
    }
    return $stmt->fetchAll();
}

function getRecentComments($limit = 5) {
    $stmt = db()->prepare("
        SELECT c.*, p.title as post_title, p.slug as post_slug
        FROM comments c
        LEFT JOIN posts p ON c.post_id = p.id
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function countComments($status = null) {
    if ($status) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM comments WHERE status = ?");
        $stmt->execute([$status]);
    } else {
        $stmt = db()->query("SELECT COUNT(*) FROM comments");
    }
    return $stmt->fetchColumn();
}

// --- USERS ---
function getUserByEmail($email) {
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function getUserById($id) {
    $stmt = db()->prepare("SELECT id, name, email, role, is_blocked, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllUsers($limit = 100) {
    $stmt = db()->prepare("SELECT id, name, email, role, is_blocked, created_at FROM users ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function countUsers() {
    return db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

// --- PAGES ---
function getPageBySlug($slug) {
    $canSeeDrafts = canEdit();
    $statusFilter = $canSeeDrafts ? "" : " AND status = 'published'";
    
    $stmt = db()->prepare("SELECT * FROM pages WHERE slug = ? " . $statusFilter);
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getPageById($id) {
    $stmt = db()->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllPages() {
    return db()->query("SELECT * FROM pages ORDER BY created_at DESC")->fetchAll();
}

// --- MEDIA ---
function getAllMedia($limit = 50) {
    $stmt = db()->prepare("
        SELECT m.*, u.name as uploader_name
        FROM media m
        LEFT JOIN users u ON m.uploaded_by = u.id
        ORDER BY m.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
// --- SETTINGS ---
function getSetting($key, $default = '') {
    try {
        $stmt = db()->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function updateSetting($key, $value) {
    $stmt = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Define APP_NAME dynamically based on database setting
if (!defined('APP_NAME')) {
    define('APP_NAME', getSetting('site_title', 'VivFramework'));
}
// --- MENUS ---
/**
 * Fetch menu items by location name or numeric menu ID.
 */
function getMenuItems($locationOrId = 'primary') {
    try {
        if (is_numeric($locationOrId)) {
            $stmt = db()->prepare("SELECT structure FROM menus WHERE id = ?");
        } else {
            $stmt = db()->prepare("SELECT structure FROM menus WHERE location = ?");
        }
        $stmt->execute([$locationOrId]);
        $json = $stmt->fetchColumn();
        return $json ? json_decode($json, true) : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Processes a string containing PHP code (e.g. from custom builders)
 */
function processDynamicContent($html) {
    if (empty($html)) return '';
    
    // Start buffering to capture processed HTML
    ob_start();
    try {
        // This trick allows executing PHP inside a string
        eval("?>$html<?php ");
    } catch (Throwable $e) {
        echo "<!-- Error processing custom design: " . h($e->getMessage()) . " -->";
        echo $html; // Fallback to raw HTML
    }
    return ob_get_clean();
}

/**
 * Centrally manages Header output
 */
function renderHeader() {
    global $_currentUser;
    if (getSetting('enable_custom_header')) {
        echo '<style>' . getSetting('custom_header_css') . '</style>';
        echo processDynamicContent(getSetting('custom_header_html'));
    } else {
        require __DIR__ . '/partials/header-default.php';
    }
}

/**
 * Centrally manages Footer output
 */
function renderFooter() {
    global $_currentUser;
    if (getSetting('enable_custom_footer')) {
        echo '<style>' . getSetting('custom_footer_css') . '</style>';
        echo processDynamicContent(getSetting('custom_footer_html'));
    } else {
        require __DIR__ . '/partials/footer-default.php';
    }
}
/**
 * Centrally manages Floating CTA Buttons output
 */
function renderFloatingCTA() {
    if (getSetting('cta_enabled', '0') !== '1') return;

    // Load Settings
    $design_mobile = getSetting('cta_design_mobile', 'simple');
    $design_desktop = getSetting('cta_design_desktop', 'pill');
    $global_visibility = getSetting('cta_visibility', 'mobile');
    
    // Load Call Settings
    $show_call = getSetting('cta_show_call', '1') === '1';
    $phone = getSetting('cta_phone', '');
    $text_call = getSetting('cta_text_call', 'Call Now');
    $bg_call = getSetting('cta_bg_call', '#2271b1');
    $vis_call = getSetting('cta_visibility_call', $global_visibility);

    // Load WA Settings
    $show_wa = getSetting('cta_show_whatsapp', '1') === '1';
    $whatsapp = getSetting('cta_whatsapp', '');
    $text_wa = getSetting('cta_text_whatsapp', 'WhatsApp');
    $bg_wa = getSetting('cta_bg_whatsapp', '#25d366');
    $vis_wa = getSetting('cta_visibility_wa', $global_visibility);

    // Return if nothing to show
    if ((!$show_call || !$phone) && (!$show_wa || !$whatsapp)) return;

    ?>
    <style>
        .cta-container { position: fixed; bottom: 0; left: 0; width: 100%; z-index: 99999; transition: all 0.3s ease; }
        .cta-btn { 
            display: flex; align-items: center; justify-content: center; gap: 10px;
            text-decoration: none; color: #fff; font-weight: 700; font-size: 15px; 
            transition: all 0.3s ease; height: 56px; flex: 1; border: none;
        }
        .cta-btn i { font-size: 18px; }

        /* Mobile Layouts */
        @media (max-width: 768px) {
            .cta-btn.hide-mobile { display: none !important; }
            .cta-desktop-wrapper { display: none !important; }
            
            .cta-mobile-style-simple { display: flex; width: 100%; box-shadow: 0 -4px 15px rgba(0,0,0,0.1); }
            .cta-mobile-style-simple .call { background: <?=$bg_call?>; }
            .cta-mobile-style-simple .wa { background: <?=$bg_wa?>; }

            .cta-mobile-style-pill { display: flex; gap: 12px; padding: 15px; background: transparent; }
            .cta-mobile-style-pill .cta-btn { border-radius: 100px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
            .cta-mobile-style-pill .call { background: <?=$bg_call?>; }
            .cta-mobile-style-pill .wa { background: <?=$bg_wa?>; }

            .cta-mobile-style-gradient { display: flex; width: 100%; background: linear-gradient(to right, <?=$bg_call?>, <?=$bg_wa?>); box-shadow: 0 -4px 15px rgba(0,0,0,0.1); }
            .cta-mobile-style-gradient .cta-btn { background: transparent; }
            .cta-mobile-style-gradient .divider { width: 1px; height: 30px; background: rgba(255,255,255,0.2); align-self: center; }
        }

        /* Desktop Layouts */
        @media (min-width: 769px) {
            .cta-btn.hide-desktop { display: none !important; }
            .cta-mobile-wrapper { display: none !important; }
            
            /* Simple (Full Bottom Bar) on Desktop */
            .cta-desktop-style-simple { 
                display: flex; position: fixed; bottom: 0; left: 0; width: 100%; 
                box-shadow: 0 -5px 25px rgba(0,0,0,0.15); z-index: 99999;
            }
            .cta-desktop-style-simple .cta-btn { height: 60px; font-size: 16px; flex: 1; border-radius: 0; }
            .cta-desktop-style-simple .call { background: <?=$bg_call?>; }
            .cta-desktop-style-simple .wa { background: <?=$bg_wa?>; }

            /* Modern Pill (Floating Actions) on Desktop */
            .cta-desktop-style-pill { 
                display: flex; flex-direction: column; gap: 15px; 
                position: fixed; right: 40px; bottom: 40px; width: auto; 
            }
            .cta-desktop-style-pill .cta-btn { width: 60px; height: 60px; border-radius: 50%; box-shadow: 0 10px 30px rgba(0,0,0,0.15); flex: none; }
            .cta-desktop-style-pill .call { background: <?=$bg_call?>; }
            .cta-desktop-style-pill .wa { background: <?=$bg_wa?>; }

            /* Gradient Bar (Centered Pill) on Desktop */
            .cta-desktop-style-gradient { 
                display: flex; position: fixed; bottom: 25px; left: 50%; width: 450px;
                transform: translateX(-50%); border-radius: 50px; overflow: hidden;
                box-shadow: 0 15px 45px rgba(0,0,0,0.2); 
                background: linear-gradient(to right, <?=$bg_call?>, <?=$bg_wa?>);
            }
            .cta-desktop-style-gradient .cta-btn { background: transparent; height: 56px; border-radius: 0; flex:1; }
            .cta-desktop-style-gradient .divider { width: 1px; height: 30px; background: rgba(255,255,255,0.1); align-self: center; }

            /* Desktop Hover Labels */
            .cta-btn { position: relative; overflow: visible; }
            .cta-btn span { 
                position: absolute; bottom: calc(100% + 15px); left: 50%; transform: translateX(-50%) translateY(10px);
                background: #333; color: #fff; padding: 7px 15px; border-radius: 6px; font-size: 13px;
                white-space: nowrap; opacity: 0; visibility: hidden; transition: all 0.3s ease;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2); font-weight: 500; pointer-events: none;
            }
            .cta-desktop-style-pill .cta-btn span { right: calc(100% + 15px); left: auto; top: 50%; transform: translateY(-50%) translateX(10px); bottom: auto; }
            .cta-btn:hover span { opacity: 1; transform: translateX(-50%) translateY(0); visibility: visible; }
            .cta-desktop-style-pill .cta-btn:hover span { transform: translateY(-50%) translateX(0); }
        }
    </style>

    <div class="cta-container">
        <!-- Mobile Wrapper -->
        <div class="cta-mobile-wrapper cta-mobile-style-<?=$design_mobile?>">
            <?php if ($show_call && $phone): ?>
            <a href="tel:<?=$phone?>" class="cta-btn call <?=($vis_call==='desktop')?'hide-mobile':''?>">
                <i class="fas fa-phone-alt"></i> <span><?=$text_call?></span>
            </a>
            <?php endif; ?>
            <?php if ($design_mobile==='gradient' && $show_call && $show_wa && $phone && $whatsapp): ?><div class="divider"></div><?php endif; ?>
            <?php if ($show_wa && $whatsapp): ?>
            <a href="https://wa.me/<?=preg_replace('/[^0-9]/','',$whatsapp)?>" target="_blank" class="cta-btn wa <?=($vis_wa==='desktop')?'hide-mobile':''?>">
                <i class="fab fa-whatsapp"></i> <span><?=$text_wa?></span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Desktop Wrapper -->
        <div class="cta-desktop-wrapper cta-desktop-style-<?=$design_desktop?>">
            <?php if ($show_call && $phone): ?>
            <a href="tel:<?=$phone?>" class="cta-btn call <?=($vis_call==='mobile')?'hide-desktop':''?>">
                <i class="fas fa-phone-alt"></i> <span><?=$text_call?></span>
            </a>
            <?php endif; ?>
            <?php if ($design_desktop==='gradient' && $show_call && $show_wa && $phone && $whatsapp): ?><div class="divider"></div><?php endif; ?>
            <?php if ($show_wa && $whatsapp): ?>
            <a href="https://wa.me/<?=preg_replace('/[^0-9]/','',$whatsapp)?>" target="_blank" class="cta-btn wa <?=($vis_wa==='mobile')?'hide-desktop':''?>">
                <i class="fab fa-whatsapp"></i> <span><?=$text_wa?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
