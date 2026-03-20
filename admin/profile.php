<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireEditorOrAdmin();

$_currentUser = currentUser();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($name && $email) {
        // Check if email already exists for OTHER users
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_currentUser['id']]);
        if ($stmt->fetch()) {
            setFlash('error', 'This email is already taken by another account.');
        } else {
            // Update basic info
            $stmt = db()->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $_currentUser['id']]);
            
            // Handle password update if provided
            if ($password) {
                if ($password === $confirm_password) {
                    if (strlen($password) >= 6) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        db()->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $_currentUser['id']]);
                        setFlash('success', 'Profile and password updated successfully!');
                    } else {
                        setFlash('error', 'Password must be at least 6 characters.');
                    }
                } else {
                    setFlash('error', 'Passwords do not match.');
                }
            } else {
                setFlash('success', 'Profile updated successfully!');
            }
            
            // Refresh session info
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            redirect(APP_URL . '/admin/profile.php');
        }
    } else {
        setFlash('error', 'Name and Email are required.');
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap profile-wrap">
    <div class="admin-page-header">
        <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            Personal Identity & Security
        </div>
        
        <form action="" method="POST" style="padding: 20px;">
            <?php csrfField(); ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                <!-- Left: Basic Info -->
                <div>
                    <h4 style="margin-bottom: 15px; color: #1d2327;">Personal Details</h4>
                    <div class="form-group">
                        <label>Username / Login</label>
                        <input type="text" value="<?= h($_currentUser['email']) ?>" disabled style="background: #f0f0f1; cursor: not-allowed;">
                        <p style="font-size: 11px; color: #646970; margin-top: 5px;">Usernames cannot be changed.</p>
                    </div>
                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" name="name" value="<?= h($_currentUser['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="email" value="<?= h($_currentUser['email']) ?>" required>
                    </div>
                </div>

                <!-- Right: Security -->
                <div style="border-left: 1px solid #c3c4c7; padding-left: 30px;">
                    <h4 style="margin-bottom: 15px; color: #1d2327;">Account Security</h4>
                    <div class="form-group">
                        <label>New Password</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="password" id="pass-new" placeholder="••••••••" style="padding-right: 32px;">
                            <i class="fas fa-eye" style="position: absolute; right: 10px; cursor: pointer; color: #646970; font-size: 14px;" onclick="togglePassVisibility('pass-new', this)"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Repeat New Password</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="confirm_password" id="pass-confirm" placeholder="••••••••" style="padding-right: 32px;">
                            <i class="fas fa-eye" style="position: absolute; right: 10px; cursor: pointer; color: #646970; font-size: 14px;" onclick="togglePassVisibility('pass-confirm', this)"></i>
                        </div>
                    </div>
                    <div style="background: #fff8e5; padding: 12px; border-left: 4px solid #ffb900; font-size: 12px; color: #3c434a;">
                        <i class="fas fa-info-circle"></i> Password must be at least 6 characters. Leave blank if you don't want to change it.
                    </div>
                </div>
            </div>

            <div style="border-top: 1px solid #c3c4c7; padding-top: 20px; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check" style="margin-right: 5px;"></i> Update Profile Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
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
