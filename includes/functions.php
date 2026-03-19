<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

// ============================================
// 🔒 AUTHENTICATION FUNCTIONS
// ============================================

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function isEditorOrAdmin() {
    return isLoggedIn() && in_array($_SESSION['user_role'], ['admin', 'editor']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
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
    if (!isEditorOrAdmin()) {
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
    return APP_URL . '/category/' . $slug;
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
    $canSeeDrafts = isEditorOrAdmin();
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
    $canSeeDrafts = isEditorOrAdmin();
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
// --- MENUS ---
/**
 * Fetch menu items by location name or numeric menu ID.
 */
function getMenuItems($locationOrId = 'primary') {
    try {
        if (is_numeric($locationOrId)) {
            $stmt = db()->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY order_index ASC");
        } else {
            $stmt = db()->prepare("
                SELECT mi.* 
                FROM menu_items mi 
                JOIN menus m ON mi.menu_id = m.id 
                WHERE m.location = ? 
                ORDER BY mi.order_index ASC
            ");
        }
        $stmt->execute([$locationOrId]);
        return $stmt->fetchAll();
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
