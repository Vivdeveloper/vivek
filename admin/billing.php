<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdmin();

// Handling Actions (STAY AT TOP FOR PRG FIX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_plan') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $description = trim($_POST['description']);
        $featuresRaw = $_POST['features'] ?? '';
        $features = array_map('trim', explode("\n", $featuresRaw));
        $features = array_filter($features); // Remove empty lines
        
        if ($name && $id) {
            updatePlan($id, $name, $price, $description, $features);
            setFlash('success', 'Plan updated successfully!');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

$plans = getPlans();

$pageTitle = 'Billing Portal';
require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap">
    <div class="admin-page-header">
        <div class="header-left">
            <h2>Billing & Plans</h2>
            <p class="text-muted">Manage website packages and global pricing</p>
        </div>
    </div>

    <div class="admin-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin-top: 30px;">
        <?php foreach($plans as $plan): 
            $features = json_decode($plan['features'], true) ?: [];
        ?>
        <div class="modern-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: #1d2327;"><?= h($plan['name']) ?></span>
                <span style="font-size: 18px; font-weight: 700; color: #2271b1;">₹<?= number_format($plan['price'], 0) ?></span>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="admin-form">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="update_plan">
                    <input type="hidden" name="id" value="<?= $plan['id'] ?>">
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: #1d2327;">Plan Name</label>
                        <input type="text" name="name" value="<?= h($plan['name']) ?>" required 
                               style="width: 100%; padding: 8px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: #1d2327;">Price (INR)</label>
                        <input type="number" name="price" value="<?= $plan['price'] ?>" step="0.01" required 
                               style="width: 100%; padding: 8px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: #1d2327;">Description</label>
                        <textarea name="description" style="width: 100%; height: 60px; padding: 8px; border: 1px solid #ccd0d4; border-radius: 4px;"><?= h($plan['description']) ?></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: #1d2327;">Features (One per line)</label>
                        <textarea name="features" style="width: 100%; height: 100px; padding: 8px; border: 1px solid #ccd0d4; border-radius: 4px; font-size: 12px;"><?= h(implode("\n", $features)) ?></textarea>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>