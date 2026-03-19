<nav class="navbar" id="navbar">
    <div class="container nav-container">
        <a href="<?= APP_URL ?>/" class="nav-logo">
            <i class="fas fa-chart-line"></i>
            <span><?= APP_NAME ?></span>
        </a>

        <div class="nav-menu" id="navMenu">
            <?php 
            $primaryMenu = getMenuItems('primary');
            foreach ($primaryMenu as $item): 
                $url = (strpos($item['url'], 'http') === 0) ? $item['url'] : (strpos($item['url'], '/') === 0 ? APP_URL . $item['url'] : $item['url']);
            ?>
                <a href="<?= $url ?>" class="nav-link" target="<?= h($item['target']) ?>"><?= h($item['title']) ?></a>
            <?php endforeach; ?>

            <?php if (isset($_currentUser) && $_currentUser): ?>
                <div class="nav-user">
                    <div class="nav-user-name">
                        <i class="fas fa-user-circle"></i> <?= h($_currentUser['name']) ?> <i class="fas fa-chevron-down" style="font-size:10px;opacity:0.5"></i>
                    </div>
                    <div class="nav-dropdown">
                        <?php if (in_array($_currentUser['role'], ['admin', 'editor'])): ?>
                            <a href="<?= APP_URL ?>/admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <?php endif; ?>
                        <a href="<?= APP_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= pageUrl('contact') ?>" class="nav-link nav-btn"><i class="fas fa-rocket"></i> Free SEO Audit</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>
