<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdmin();

$id = intval($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    redirect(APP_URL . '/admin/users.php');
}

$_currentUser = currentUser();

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    requireEditAccess();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    $planId = intval($_POST['plan_id'] ?? 0) ?: null;
    
    if ($name && $email && $role) {
        // Check email uniqueness (excluding current)
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            setFlash('error', 'Email already in use by another user.');
        } else {
            // Permissions logic
            $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : json_encode([]);

            // Update Base
            $stmt = db()->prepare("UPDATE users SET name = ?, email = ?, role = ?, permissions = ?, plan_id = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $permissions, $planId, $id]);
            
            // Update Password if not empty
            if ($password) {
                if (strlen($password) < 6) {
                    setFlash('error', 'Password must be at least 6 characters. Basic info updated.');
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    db()->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $id]);
                    setFlash('success', 'User updated comprehensively!');
                }
            }
            // Redirect to self after update (Stay on page just like Post Edit)
            setFlash('success', 'User modifications saved successfully.');
            redirect(APP_URL . "/admin/user-edit.php?id=$id");
        }
    } else {
        setFlash('error', 'All fields are required.');
    }
}

$pageTitle = 'Edit User: ' . $user['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap">
    <div class="admin-page-header">
        <h2>Edit User</h2>
        <a href="users.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            Full User Management
        </div>
        
        <form action="" method="POST" style="padding: 24px;">
            <?php csrfField(); ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                
                <!-- Left: Identity -->
                <div>
                    <h4 style="margin-bottom: 20px; color: #1d2327; font-size: 15px;"><i class="fas fa-id-card"></i> Identity & Role</h4>
                    
                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" name="name" value="<?= h($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Login Email</label>
                        <input type="email" name="email" value="<?= h($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Account Role</label>
                        <select name="role" <?= ($user['id'] == $_currentUser['id']) ? 'disabled' : '' ?>>
                            <option value="editor" <?= $user['role']==='editor'?'selected':'' ?>>Editor (Restricted)</option>
                            <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Administrator (Full)</option>
                        </select>
                        <?php if ($user['id'] == $_currentUser['id']): ?>
                            <input type="hidden" name="role" value="<?= $user['role'] ?>">
                            <p style="font-size: 11px; color: #d63638; margin-top: 5px;">You cannot change your own role for security reasons.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Security -->
                <div style="border-left: 1px solid #c3c4c7; padding-left: 40px;">
                    <h4 style="margin-bottom: 20px; color: #1d2327; font-size: 15px;"><i class="fas fa-lock"></i> Security Overrides</h4>
                    
                    <div class="form-group">
                        <label>Reset Password</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="password" id="pass-reset" placeholder="Enter new password to reset">
                            <i class="fas fa-eye" style="position: absolute; right: 10px; cursor: pointer; color: #646970; font-size: 14px;" onclick="togglePassVisibility('pass-reset', this)"></i>
                        </div>
                        <p style="font-size: 11px; color: #646970; margin-top: 8px;">Leave blank to keep the current password. If changed, the user must use the new one on next login.</p>
                    </div>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h4 style="margin-bottom: 20px; color: #1d2327; font-size: 15px;"><i class="fas fa-shield-alt"></i> Account Status</h4>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #ccd0d4;">
                            <div>
                                <strong style="display: block; font-size: 13px; color: <?= $user['is_blocked'] ? '#d63638' : '#2271b1' ?>;">
                                    <?= $user['is_blocked'] ? 'Account is Currently Blocked' : 'Account is Active and Healthy' ?>
                                </strong>
                                <span style="font-size: 11px; color: #646970;">Blocked users cannot login or access any site data.</span>
                            </div>
                            <?php if ($user['id'] != $_currentUser['id'] && canEdit()): ?>
                                <button type="button" class="btn <?= $user['is_blocked'] ? 'btn-outline' : 'btn-danger' ?>" onclick="if(confirm('<?= $user['is_blocked'] ? 'Restore access for this user?' : 'Are you sure you want to block this user from logging in?' ?>')) { document.getElementById('toggle-block-form').submit(); }">
                                    <i class="fas <?= $user['is_blocked'] ? 'fa-unlock' : 'fa-ban' ?>" style="margin-right: 5px;"></i>
                                    <?= $user['is_blocked'] ? 'Restore Access' : 'Block Access' ?>
                                </button>
                            <?php elseif ($user['id'] == $_currentUser['id']): ?>
                                <span class="status-badge" style="background:#f0f0f1; color:#646970; border: 1px solid #ccd0d4;">You are logging in as this person</span>
                            <?php else: ?>
                                <span class="status-badge" style="background:#f0f0f1; color:#646970; border: 1px solid #ccd0d4;">Read-Only Mode</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="background: #f0f6fb; padding: 15px; border-left: 4px solid #2271b1; color: #135e96; font-size: 12px; margin-top: 30px;">
                        <strong>Quick Audit Metadata:</strong><br>
                        Registered on: <?= formatDate($user['created_at']) ?><br>
                        Account ID: #<?= $user['id'] ?>
                    </div>

                    <!-- Billing Plan Assignment -->
                    <div style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; color: #1d2327; font-size: 14px;"><i class="fas fa-credit-card"></i> Assigned Website Plan</h4>
                        <div class="form-group">
                            <select name="plan_id" style="width: 100%; padding: 10px; border-radius: 6px;">
                                <option value="">--- No Plan Assigned ---</option>
                                <?php 
                                $allPlans = getPlans();
                                foreach($allPlans as $plan): ?>
                                <option value="<?= $plan['id'] ?>" <?= $user['plan_id'] == $plan['id'] ? 'selected' : '' ?>>
                                    <?= h($plan['name']) ?> (₹<?= number_format($plan['price'], 0) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p style="font-size: 11px; color: #646970; margin-top: 8px;">The user will only be able to view details of their assigned plan.</p>
                    </div>
                </div>

            </div>

            <!-- Permissions Management -->
            <div style="margin-top: 40px; padding: 25px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px;">
                <h4 style="margin-bottom: 20px; color: #1d2327; font-size: 15px;"><i class="fas fa-user-shield"></i> Admin Menu Access Control</h4>
                <p class="text-muted" style="font-size: 12px; margin-bottom: 20px;">Unchecking a module will hide it from the user's sidebar and block direct access to those management pages.</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <?php
                    $admin_menu_master = getAdminMenu();
                    $user_perm = json_decode($user['permissions'] ?? '[]', true);
                    if (empty($user_perm)) {
                        if ($user['role'] === 'editor') {
                            $user_perm = ['dashboard'];
                        } else {
                            $user_perm = array_column(array_filter($admin_menu_master, fn($m) => isset($m['key'])), 'key');
                        }
                    }
                    foreach($admin_menu_master as $m): 
                        if ($m['type'] === 'separator') continue;
                        $key = $m['key'];
                        $label = $m['label'];
                    ?>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid #f0f0f1; border-radius: 6px; transition: 0.2s; font-size: 13px;">
                        <input type="checkbox" name="permissions[]" value="<?= $key ?>" <?= in_array($key, $user_perm) ? 'checked' : '' ?>>
                        <span><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #f0f0f1; display: flex; justify-content: flex-end; gap: 15px;">
                <a href="users.php" class="btn btn-outline"><?= canEdit() ? 'Discard Changes' : 'Back to Users' ?></a>
                <?php if (canEdit()): ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save" style="margin-right: 5px;"></i> Update User Record
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Hidden Action Form (Must be outside main form) -->
    <form id="toggle-block-form" action="users.php" method="POST" style="display:none;">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="toggle_block">
        <input type="hidden" name="id" value="<?= $id ?>">
    </form>
</div>

<script>
// === SMART EDITING SUITE ===
let formDirty = false;
const editForm = document.querySelector('form[action=""]');

// 1. Unsaved Changes Protection
if (editForm) {
    // Mark form as dirty on input
    editForm.addEventListener('input', () => formDirty = true);
    editForm.addEventListener('change', () => formDirty = true);

    // Reset dirty flag on legitimate submit
    editForm.addEventListener('submit', () => formDirty = false);

    // Warn on navigation
    window.addEventListener('beforeunload', (e) => {
        if (formDirty) {
            e.preventDefault();
            e.returnValue = ''; // Standard browser confirmation
        }
    });

// 2. Click Interceptor for navigation links
    document.querySelectorAll('a[href="users.php"], .btn-outline').forEach(link => {
        link.addEventListener('click', (e) => {
            if (formDirty) {
                if (!confirm('You have unsaved changes. Are you sure you want to discard them?')) {
                    e.preventDefault();
                }
            }
        });
    });

    // 3. Global Hotkey Support (Ctrl+S or Cmd+S)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            formDirty = false; // Disarm the exit warning before saving
            editForm.submit();
        }
    });
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
