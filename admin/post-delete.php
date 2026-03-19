<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession(); requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = intval($_POST['id'] ?? 0);
    if ($id) { db()->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]); setFlash('success', 'Post deleted.'); }
}
redirect(APP_URL . '/admin/posts.php');
