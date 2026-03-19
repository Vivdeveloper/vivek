<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3><i class="fas fa-chart-line"></i> <?= APP_NAME ?></h3>
                <p><?= h(getSetting('footer_desc', "We're a full-service SEO and web design agency helping businesses dominate search results and grow online. Custom strategies, transparent reporting, real results.")) ?></p>
                <div class="footer-social">
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Services</h4>
                <ul>
                    <li><a href="<?= APP_URL ?>/#services">SEO Optimization</a></li>
                    <li><a href="<?= APP_URL ?>/#services">Website Design</a></li>
                    <li><a href="<?= APP_URL ?>/#services">Digital Marketing</a></li>
                    <li><a href="<?= APP_URL ?>/#services">E-Commerce</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <?php 
                    $footerMenu = getMenuItems('footer');
                    if (!empty($footerMenu)): 
                        foreach ($footerMenu as $item): 
                            $url = (strpos($item['url'], 'http') === 0) ? $item['url'] : (strpos($item['url'], '/') === 0 ? APP_URL . $item['url'] : $item['url']);
                    ?>
                        <li><a href="<?= $url ?>" target="<?= h($item['target']) ?>"><?= h($item['title']) ?></a></li>
                    <?php endforeach; else: ?>
                        <li><a href="<?= pageUrl('about') ?>">About Us</a></li>
                        <li><a href="<?= APP_URL ?>/blog.php">Blog</a></li>
                        <li><a href="<?= pageUrl('contact') ?>">Contact</a></li>
                        <li><a href="<?= pageUrl('privacy-policy') ?>">Privacy Policy</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get in Touch</h4>
                <p style="margin-bottom: 8px;"><i class="fas fa-envelope" style="color: var(--accent-1); margin-right: 6px;"></i> <?= h(getSetting('footer_email', 'contact@seowebsitedesigner.com')) ?></p>
                <p style="margin-bottom: 8px;"><i class="fas fa-phone-alt" style="color: var(--accent-1); margin-right: 6px;"></i> <?= h(getSetting('footer_phone', '+91 123 456 7890')) ?></p>
                <p style="margin-bottom: 16px;"><i class="fas fa-map-marker-alt" style="color: var(--accent-1); margin-right: 6px;"></i> <?= h(getSetting('footer_address', 'Mumbai, India')) ?></p>
                <form class="footer-newsletter" onsubmit="event.preventDefault(); alert('Subscribed!'); this.reset();">
                    <input type="email" placeholder="Your email..." required>
                    <button type="submit"><i class="fas fa-arrow-right"></i></button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved. | Built with <?= APP_NAME ?></p>
        </div>
    </div>
</footer>
