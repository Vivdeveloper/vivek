<?php
require_once __DIR__ . '/includes/functions.php';
startSecureSession();
if (isLoggedIn()) { redirect(APP_URL . '/'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrf()) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($name) < 2) { setFlash('error', 'Name must be at least 2 characters.'); }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { setFlash('error', 'Valid email required.'); }
        elseif (strlen($password) < 6) { setFlash('error', 'Password must be at least 6 characters.'); }
        elseif ($password !== $confirmPassword) { setFlash('error', 'Passwords do not match.'); }
        else {
            $existing = getUserByEmail($email);
            if ($existing) {
                setFlash('error', 'Email already registered.');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
                $stmt = db()->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$name, $email, $hashedPassword]);
                $userId = db()->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                setFlash('success', 'Welcome aboard, ' . $name . '!');
                redirect(APP_URL . '/');
            }
        }
    } else {
        setFlash('error', 'Invalid form submission.');
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon-wrapper">
                    <i class="fas fa-rocket auth-icon"></i>
                </div>
                <h1>Create Account</h1>
                <p>Join our community of writers and readers</p>
            </div>
            <form action="" method="POST" class="auth-form">
                <?php csrfField(); ?>
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="name" name="name" placeholder="John Doe" required minlength="2">
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Min 6 characters" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-shield-alt"></i> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; font-size: 15px; margin-top: 8px;">
                    <i class="fas fa-arrow-right"></i> Create Account
                </button>
            </form>
            <div class="auth-footer">
                <p>Already have an account? <a href="<?= APP_URL ?>/login.php">Sign in →</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
