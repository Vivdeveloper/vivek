<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requirePermission('users');

$_currentUser = currentUser();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    requireEditAccess();
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    // Create User (Simplifed for this interface)
    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if ($name && $email && $password) {
            if (strlen($password) < 6) {
                setFlash('error', 'Password must be at least 6 characters.');
            } else {
                $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    setFlash('error', 'Email already registered.');
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    db()->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
                        ->execute([$name, $email, $hashed, $role]);
                    setFlash('success', 'User created successfully!');
                }
            }
        } else {
            setFlash('error', 'All fields are required.');
        }
    }
    
    // Update User Info (Basic + Password)
    if ($action === 'update_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($name && $email && $id) {
            // Check if email already exists for OTHER users
            $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                setFlash('error', 'A user with this email already exists.');
            } else {
                // Update basic info
                db()->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")
                    ->execute([$name, $email, $id]);
                
                // Update password if provided
                if ($password) {
                    if (strlen($password) < 6) {
                        setFlash('error', 'Password must be at least 6 characters. Basic info updated.');
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        db()->prepare("UPDATE users SET password = ? WHERE id = ?")
                            ->execute([$hashed, $id]);
                        setFlash('success', 'User info and password updated!');
                    }
                } else {
                    setFlash('success', 'User updated successfully!');
                }
            }
        }
        redirect(APP_URL . '/admin/users.php');
    }

    // Handle Bulk Actions
    $bulkAction = $_POST['action'] ?? '';
    $ids = $_POST['ids'] ?? [];
    if ($bulkAction && !empty($ids)) {
        $validIds = array_map('intval', $ids);
        // Remove current user from bulk actions to prevent self-lockout
        $validIds = array_filter($validIds, fn($id) => $id != $_currentUser['id']);
        
        if (!empty($validIds)) {
            $placeholders = implode(',', array_fill(0, count($validIds), '?'));
            
            if ($bulkAction === 'bulk_block') {
                db()->prepare("UPDATE users SET is_blocked = 1 WHERE id IN ($placeholders)")->execute($validIds);
                setFlash('success', count($validIds) . ' users blocked.');
            } elseif ($bulkAction === 'bulk_unblock') {
                db()->prepare("UPDATE users SET is_blocked = 0 WHERE id IN ($placeholders)")->execute($validIds);
                setFlash('success', count($validIds) . ' users unblocked.');
            } elseif ($bulkAction === 'bulk_delete') {
                requireAdmin();
                db()->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($validIds);
                setFlash('success', count($validIds) . ' users permanently deleted.');
            }
        }
        redirect(APP_URL . '/admin/users.php');
    }

    if ($id) {
        if ($action === 'toggle_block') {
            if ($id != $_currentUser['id']) {
                // More robust flip using SQL IF logic
                db()->prepare("UPDATE users SET is_blocked = IF(is_blocked = 1, 0, 1) WHERE id = ?")->execute([$id]);
                setFlash('success', 'User status updated successfully.');
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

<div class="wrap">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Users</h2>
            <p class="text-muted">Manage your site users and personnel</p>
        </div>
        <?php if (canEdit()): ?>
        <button class="btn btn-primary" onclick="toggleAddUser()"><i class="fas fa-plus"></i> Add New</button>
        <?php endif; ?>
    </div>

    <!-- Create User Section -->
    <div id="add-user-section" class="admin-card" style="display: none; margin-bottom: 25px;">
        <div class="admin-card-header">Add New User</div>
        <form action="" method="POST" style="padding: 20px;">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="create_user">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Ex: John Doe">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="john@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" name="password" id="pass-create" required placeholder="Minimum 6 characters" style="padding-right: 32px;">
                        <i class="fas fa-eye" style="position: absolute; right: 10px; cursor: pointer; color: #646970; font-size: 14px;" onclick="togglePassVisibility('pass-create', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Assigned Permission Tier (Role)</label>
                    <select name="role">
                        <option value="editor">Editor (Restricted by permissions)</option>
                        <option value="admin">Administrator (Full Access)</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid #f0f0f1; padding-top: 15px;">
                <button type="button" class="btn btn-outline" onclick="toggleAddUser()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>

    <!-- Filter Tabs (Post Style) -->
    <div class="filter-tabs-container">
        <div class="filter-tabs">
            <?php
            $counts = [
                'all' => db()->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'admin' => db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
                'editor' => db()->query("SELECT COUNT(*) FROM users WHERE role = 'editor'")->fetchColumn(),
                'user' => db()->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn()
            ];
            $currentFilter = $_GET['role'] ?? 'all';
            ?>
            <a href="?role=all" class="filter-tab <?= $currentFilter === 'all' ? 'active' : '' ?>">All <span class="badge"><?= $counts['all'] ?></span></a>
            <a href="?role=admin" class="filter-tab <?= $currentFilter === 'admin' ? 'active' : '' ?>">Administrators <span class="badge"><?= $counts['admin'] ?></span></a>
            <a href="?role=editor" class="filter-tab <?= $currentFilter === 'editor' ? 'active' : '' ?>">Editors <span class="badge"><?= $counts['editor'] ?></span></a>
            <a href="?role=user" class="filter-tab <?= $currentFilter === 'user' ? 'active' : '' ?>">Users <span class="badge"><?= $counts['user'] ?></span></a>
        </div>
    </div>

    <form action="" method="POST" id="bulk-form">
        <?php csrfField(); ?>
        <div class="bulk-actions-container">
            <select name="action">
                <option value="">Bulk Actions</option>
                <option value="bulk_block">Block Selected</option>
                <option value="bulk_unblock">Unblock Selected</option>
                <option value="bulk_delete">Delete Permanently</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
        </div>

        <div class="modern-card no-padding overflow-hidden">
            <table class="modern-table users-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="check-all"></th>
                        <th>Name</th>
                        <th style="width: 150px;">Role</th>
                        <th style="width: 180px;">Assigned Plan</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 120px;">Date Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $filteredUsers = $users;
                    if ($currentFilter !== 'all') {
                        $filteredUsers = array_filter($users, fn($u) => $u['role'] === $currentFilter);
                    }
                    foreach ($filteredUsers as $user): 
                    ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $user['id'] ?>" class="item-check" <?= $user['id'] == $_currentUser['id'] ? 'disabled' : '' ?>></td>
                        <td>
                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                <div class="user-avatar-text"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                <div class="title-cell">
                                    <strong><?= h($user['name']) ?></strong>
                                    <small><?= h($user['email']) ?></small>
                                     <div class="row-actions posts-row-actions">
                                         <span class="edit"><a href="user-edit.php?id=<?= $user['id'] ?>"><?= canEdit() ? 'Edit' : 'View' ?></a></span> 
                                         <?php if (canEdit() && $user['id'] != $_currentUser['id']): ?>
                                         <span class="row-actions-sep">|</span>
                                         <span class="delete"><a href="javascript:void(0)" onclick="if(confirm('Permanently delete this user?')) submitSingleAction(<?= $user['id'] ?>, 'delete')" class="posts-row-action-btn--danger">Delete</a></span>
                                         <?php endif; ?>
                                     </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="user-role-badge role-<?= h($user['role']) ?>">
                                <i class="fas <?= ($user['role'] === 'admin') ? 'fa-user-shield' : (($user['role'] === 'editor') ? 'fa-user-edit' : 'fa-user') ?>" style="margin-right: 5px;"></i>
                                <?= ($user['role'] === 'admin') ? 'Administrator' : (($user['role'] === 'editor') ? 'Editor' : 'Subscriber') ?>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $userPlan = getPlanByUserId($user['id']);
                                if ($userPlan): ?>
                                    <div class="user-plan-badge">
                                        <i class="fas fa-gem" style="margin-right: 5px; color: #6366f1;"></i>
                                        <?= h($userPlan['name']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 11px;">No active plan</span>
                                <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $user['is_blocked'] ? 'trash' : 'published' ?>">
                                <?= $user['is_blocked'] ? 'DISABLED' : 'ACTIVE' ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted"><?= formatDate($user['created_at']) ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    <form action="" method="POST" id="single-action-form" style="display:none;">
        <?php csrfField(); ?>
        <input type="hidden" name="id" id="single-action-id">
        <input type="hidden" name="action" id="single-action-type">
        <input type="hidden" name="role" id="single-action-val">
    </form>
</div>

<script>
function submitSingleAction(id, action, val = '') {
    document.getElementById('single-action-id').value = id;
    document.getElementById('single-action-type').value = action;
    document.getElementById('single-action-val').value = val;
    document.getElementById('single-action-form').submit();
}

function togglePassVisibility(inputId, icon) {
    const field = document.getElementById(inputId);
    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function toggleAddUser() {
    const section = document.getElementById('add-user-section');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
}
function toggleEditUser(id) {
    const el = document.getElementById('edit-user-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
document.getElementById('check-all') && document.getElementById('check-all').addEventListener('change', function() {
    const checks = document.querySelectorAll('.item-check:not(:disabled)');
    checks.forEach(c => c.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
