<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireLogin();

$pageTitle = 'My Website Plan';
require_once __DIR__ . '/includes/header.php';

$user = currentUser();
$plan = getPlanByUserId($user['id']);
?>

<div class="wrap">
    <div class="premium-portal-header animate-fade-in">
        <div class="portal-badge"><i class="fas fa-gem"></i> Client Subscription</div>
        <h2>Active Website Package</h2>
        <p>Your portal for managing active services and website performance within
            <?= APP_NAME?>.
        </p>
    </div>

    <?php if ($plan):
    $features = json_decode($plan['features'], true) ?: [];
?>
    <div class="premium-plan-card animate-slide-up">
        <div class="glow-effect"></div>
        <div class="card-inner">
            <div class="card-grid">
                <!-- Left: Plan Main Info -->
                <div class="plan-main">
                    <div class="plan-status-badge">
                        <span class="pulse-dot"></span> Active Service
                    </div>
                    <h1 class="plan-display-name">
                        <?= h($plan['name'])?>
                    </h1>
                    <div class="plan-price-display">
                        <span class="currency">₹</span>
                        <span class="amount">
                            <?= number_format($plan['price'], 0)?>
                        </span>
                        <span class="period">one-time payment</span>
                    </div>

                    <div class="plan-description">
                        <?= h($plan['description'])?>
                    </div>

                    <div class="plan-footer-notes">
                        <i class="fas fa-info-circle"></i> Support is included for 12 months from activation.
                    </div>
                </div>

                <!-- Right: Features List -->
                <div class="plan-features-section">
                    <h3>Included Features & Services</h3>
                    <div class="features-flex-grid">
                        <?php foreach ($features as $feature): ?>
                        <div class="feature-capsule">
                            <i class="fas fa-check-circle"></i>
                            <span>
                                <?= h($feature)?>
                            </span>
                        </div>
                        <?php
    endforeach; ?>
                    </div>

                    <div class="contact-support-box">
                        <strong>Need an upgrade?</strong>
                        <p>Contact us on WhatsApp to transition to a higher tier plan.</p>
                        <?php
    $siteDomain = parse_url(APP_URL, PHP_URL_HOST) ?: $_SERVER['HTTP_HOST'];
    $whatsappText = urlencode("Hello, I would like to upgrade my plan for " . $siteDomain . " (User: " . $user['name'] . ")");
?>
                        <a href="https://wa.me/919987842957?text=<?= $whatsappText?>" class="btn-glow-premium">
                            <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
else: ?>
    <div class="no-plan-assigned-card">
        <div class="empty-icon"><i class="fas fa-ghost"></i></div>
        <h3>No Active Plan</h3>
        <p>It seems your account hasn't been assigned a website service package yet. Contact the administrator to get
            started.</p>
        <a href="<?= APP_URL?>/admin/index.php" class="btn btn-primary">Return to Dashboard</a>
    </div>
    <?php
endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>