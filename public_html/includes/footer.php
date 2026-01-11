    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. Alla rättigheter reserverade.</p>
        <?php
        $siteEmail = $settings->get('site_email', '');
        if ($siteEmail):
        ?>
        <p><a href="mailto:<?php echo htmlspecialchars($siteEmail); ?>">Kontakta oss</a></p>
        <?php endif; ?>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html>
