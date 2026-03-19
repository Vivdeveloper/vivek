    </main>

    <?php if (($page['template'] ?? 'default') !== 'canvas'): ?>
        <?php renderFooter(); ?>
    <?php endif; ?>

    <?php renderFloatingCTA(); ?>

    <script src="<?= APP_URL ?>/assets/js/main.js"></script>

    <!-- Footer Scripts from Admin -->
    <?php $footerCode = getSetting('custom_code_footer'); if($footerCode) echo $footerCode; ?>
    <?php $footerScripts = getSetting('footer_scripts'); if($footerScripts) echo $footerScripts; ?>
</body>
</html>
