    </main>

    <?php if (($page['template'] ?? 'default') !== 'canvas'): ?>
        <?php renderFooter(); ?>
    <?php endif; ?>

    <script src="<?= APP_URL ?>/assets/js/main.js"></script>

    <!-- Footer Scripts from Admin -->
    <?php $footerScripts = getSetting('footer_scripts'); if($footerScripts): ?>
    <?= $footerScripts ?>
    <?php endif; ?>
</body>
</html>
