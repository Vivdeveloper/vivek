<?php
require_once __DIR__ . '/includes/functions.php';
startSecureSession();
if (isLoggedIn()) { redirect(isAdmin() ? APP_URL . '/admin/' : APP_URL . '/'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrf()) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($email && $password) {
            try {
                $user = getUserByEmail($email);
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['is_blocked']) {
                        setFlash('error', 'Your account has been blocked. Contact admin.');
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        regenerateCsrfToken(); // New secure session
                        setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                        redirect(in_array($user['role'], ['admin', 'editor']) ? APP_URL . '/admin/' : APP_URL . '/');
                    }
                } else {
                    setFlash('error', 'Invalid email or password.');
                }
            } catch (PDOException $e) {
                // Log the error for debugging, but show a generic message to the user
                error_log("Database error during login: " . $e->getMessage());
                setFlash('error', 'A database error occurred. Please try again later.');
            }
        } else {
            setFlash('error', 'All fields are required.');
        }
    } else {
        setFlash('error', 'Invalid form submission.');
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Reset and Base for Login Only */
body.login-page-v2 { background: #ffffff !important; display: block !important; padding: 0 !important; margin: 0 !important; overflow: hidden; }
.auth-wrapper-v2 { display: flex; min-height: 100vh; width: 100%; font-family: 'Inter', sans-serif; }
.auth-left-col { flex: 1; display: flex; align-items: center; justify-content: center; padding: 60px; background: #fff; }
.auth-right-col { flex: 1.1; background: #f3f9f5; display: flex; align-items: center; justify-content: center; padding: 40px; margin: 20px; border-radius: 40px; }
.auth-form-container { width: 100%; max-width: 400px; text-align: center; }
.auth-right-content { text-align: center; }
.auth-right-img { max-width: 90%; border-radius: 20px; margin-bottom: 30px; }

/* Custom Inputs & Buttons */
.auth-head h1 { font-size: 34px; font-weight: 800; color: #000; margin-bottom: 12px; }
.auth-head p { color: #666; font-size: 15px; margin-bottom: 40px; line-height: 1.6; }
.auth-form-v2 .form-group-v2 { margin-bottom: 20px; text-align: left; }
.auth-form-v2 input { width: 100%; padding: 18px 25px; border: 1px solid #e0e0e0; border-radius: 50px; font-size: 15px; outline: none; transition: all 0.3s; color: #333; }
.auth-form-v2 input:focus { border-color: #000; box-shadow: 0 0 0 4px rgba(0,0,0,0.03); }
.forgot-pass { display: block; text-align: right; font-size: 13px; color: #666; margin-top: 8px; font-weight: 600; text-decoration: none; }

.btn-login-v2 { width: 100%; margin-top: 30px; background: #000; color: #fff; padding: 18px; border-radius: 50px; font-weight: 700; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s; }
.btn-login-v2:hover { background: #222; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

/* Divider */
.divider-v2 { position: relative; margin: 40px 0; text-align: center; }
.divider-v2::before { content: ""; position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: #eee; z-index: 1; }
.divider-v2 span { position: relative; z-index: 2; background: #fff; padding: 0 15px; color: #888; font-size: 13px; font-weight: 600; }

/* Social Buttons */
.social-login-group { display: flex; justify-content: center; gap: 20px; margin-bottom: 40px; }
.social-btn { width: 64px; height: 64px; border-radius: 50%; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #000; transition: all 0.3s; cursor: pointer; background: #fff; }
.social-btn:hover { background: #f8f8f8; transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

.auth-foot-v2 { font-size: 14px; color: #666; }
.auth-foot-v2 a { color: #5b9279; font-weight: 700; text-decoration: none; }
.auth-foot-v2 a:hover { text-decoration: underline; }

/* Responsive */
@media (max-width: 900px) {
    .auth-right-col { display: none; }
    .auth-left-col { padding: 40px 20px; }
}
</style>

<body class="login-page-v2">
    <div class="auth-wrapper-v2">
        <!-- Left Side: Login Form -->
        <div class="auth-left-col">
            <div class="auth-form-container">
                <div class="auth-head">
                    <h1>Welcome back!</h1>
                    <p>Simplify your workflow and boost your productivity with <?= APP_NAME ?>. Get started for free.</p>
                </div>

                <form action="" method="POST" class="auth-form-v2">
                    <?php csrfField(); ?>
                    <div class="form-group-v2">
                        <input type="email" name="email" placeholder="Email Address" required autocomplete="email">
                    </div>
                    <div class="form-group-v2">
                        <input type="password" name="password" placeholder="Password" required>
                        <a href="#" class="forgot-pass">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login-v2">Login</button>

                    <div class="divider-v2">
                        <span>or continue with</span>
                    </div>

                    <div class="social-login-group">
                        <div class="social-btn"><i class="fab fa-google"></i></div>
                        <div class="social-btn"><i class="fab fa-apple"></i></div>
                        <div class="social-btn"><i class="fab fa-facebook-f"></i></div>
                    </div>

                    <div class="auth-foot-v2">
                        Not a member? <a href="<?= APP_URL ?>/register.php">Register now</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Illustration -->
        <div class="auth-right-col">
            <div class="auth-right-content">
                <img src="/assets/img/login_illustration.png" alt="Illustration" class="auth-right-img">
                <h2 style="font-size: 26px; font-weight: 700; margin-bottom: 12px; color: #000;">Make your work easier and organized</h2>
                <p style="color: #666; font-size: 15px;">with <strong><?= APP_NAME ?></strong></p>
                
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 30px;">
                    <span style="width: 8px; height: 8px; background: #ddd; border-radius: 50%;"></span>
                    <span style="width: 8px; height: 8px; background: #ddd; border-radius: 50%;"></span>
                    <span style="width: 24px; height: 8px; background: #000; border-radius: 20px;"></span>
                </div>
            </div>
        </div>
    </div>
</body>

<?php 
// We skip the standard header/footer as this is a custom landing-style page
exit; 
?>
