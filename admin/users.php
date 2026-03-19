<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdmin();

$_currentUser = currentUser();

// Handle Form Submissions (Before any output to avoid headers already sent)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    // Create New User
    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if ($name && $email && $password) {
            // Check if email already exists
            $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                setFlash('error', 'A user with this email already exists.');
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
                    ->execute([$name, $email, $hashed, $role]);
                setFlash('success', 'User created successfully!');
                redirect(APP_URL . '/admin/users.php');
            }
        } else {
            setFlash('error', 'All fields are required.');
        }
    }

    if ($id) {
        if ($action === 'toggle_block') {
            if ($id != $_currentUser['id']) {
                db()->prepare("UPDATE users SET is_blocked = NOT is_blocked WHERE id = ?")->execute([$id]);
                setFlash('success', 'User status updated.');
            } else {
                setFlash('error', 'You cannot block your own account.');
            }
        } elseif ($action === 'change_role') {
            $role = $_POST['role'] ?? '';
            if (in_array($role, ['admin', 'editor', 'user'])) {
                if ($id != $_currentUser['id']) {
                    db()->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $id]);
                    setFlash('success', 'User role updated.');
                } else {
                    setFlash('error', 'You cannot change your own role here.');
                }
            }
        } elseif ($action === 'delete') {
            if ($id != $_currentUser['id']) {
                db()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                setFlash('success', 'User deleted.');
            } else { 
                setFlash('error', 'Cannot delete your own account.'); 
            }
        }
    }
    redirect(APP_URL . '/admin/users.php');
}

$pageTitle = 'Manage Users';
require_once __DIR__ . '/includes/header.php';
$users = getAllUsers();
?>

<div class="users-container admin-card">
    <div class="admin-card-header" style="justify-content: space-between; align-items: center; display: flex;">
        <h3><i class="fas fa-users"></i> User Management</h3>
        <button class="btn btn-primary" onclick="toggleAddUser()"><i class="fas fa-user-plus"></i> Add New User</button>
    </div>

    <!-- Create User Section (Initially Hidden) -->
    <div id="add-user-section" style="display: none; padding: 24px; background: #f8faff; border-bottom: 1px solid var(--border-color); border-radius: 4px; margin: 15px;">
        <h4 style="margin-bottom: 20px; font-weight: 600; color: var(--text-primary); font-size: 16px;">Create New Administrator or Editor</h4>
        <form action="" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="create_user">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required placeholder="Ex: John Doe">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="user">User</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; font-weight: 600;">Create User</button>
            </div>
        </form>
    </div>

    <div class="modern-card no-padding overflow-hidden">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>User Info</th>
                    <th>Role / Position</th>
                    <th>Status</th>
                    <th>Joined on</th>
                    <th width="120">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--accent-primary), #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 2px;"><?= h($user['name']) ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= h($user['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <form action="" method="POST" class="inline-form">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                            <select name="role" class="form-control" onchange="this.form.submit()" <?= ($user['id'] == $_currentUser['id']) ? 'disabled' : '' ?> style="height: 36px; padding: 0 10px; font-size: 13px; font-weight: 500; border-radius: 8px; width: 120px; <?= $user['role'] === 'admin' ? 'border-color: #ffd700; background: #fffdf0;' : '' ?>">
                                <option value="user" <?= $user['role']==='user'?'selected':'' ?>>User</option>
                                <option value="editor" <?= $user['role']==='editor'?'selected':'' ?>>Editor</option>
                                <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($user['id'] == $_currentUser['id'] || !$user['is_blocked']): ?>
                            <span class="status-badge" style="background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;">Active</span>
                        <?php else: ?>
                            <span class="status-badge" style="background: #fff1f0; color: #f5222d; border: 1px solid #ffa39e;">Blocked</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--text-muted); font-size: 13px;">
                        <small><?= formatDate($user['created_at']) ?></small>
                    </td>
                    <td>
                        <div class="row-actions">
                            <?php if ($user['id'] != $_currentUser['id']): ?>
                            <form action="" method="POST" class="inline-form" style="display: inline-block;">
                                <?php csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_block">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <button type="submit" class="action-btn" title="<?= $user['is_blocked'] ? 'Unblock Account' : 'Block Account' ?>" style="color: <?= $user['is_blocked'] ? '#27ae60' : '#f2994a' ?>;">
                                    <i class="fas fa-<?= $user['is_blocked'] ? 'unlock' : 'user-slash' ?>"></i>
                                </button>
                            </form>
                            
                            <form action="" method="POST" class="inline-form" onsubmit="return confirm('Permanently delete this user? This cannot be undone.')" style="display: inline-block;">
                                <?php csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <button type="submit" class="action-btn trash" title="Delete User"><i class="fas fa-trash-alt"></i></button>
                            </form>
                            <?php else: ?>
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 11px; padding: 4px 10px;">Self Account</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleAddUser() {
    const section = document.getElementById('add-user-section');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
    if (section.style.display === 'block') {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<style>
.users-container { margin: 20px 0; }
.status-badge {
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
}
.row-actions { display: flex; gap: 8px; justify-content: flex-end; }
.action-btn {
    width: 36px; height: 36px;
    border: 1px solid var(--border-color);
    background: white;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-primary);
}
.action-btn:hover { background: var(--bg-tertiary); border-color: var(--text-muted); }
.action-btn.trash:hover { color: #f5222d; border-color: #ffa39e; background: #fff1f0; }

/* Custom Form Styles for the creation form */
.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.form-control {
    width: 100%;
    height: 44px;
    padding: 10px 16px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}
.form-control:focus { border-color: var(--accent-primary); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
