<?php
require_once 'includes/header.php';

// Hämta inställningar
$settings = Settings::getInstance();
$siteDescription = $settings->get('site_description', '');
?>

<main>
    <section class="content">
        <h1>Om oss</h1>
        <?php if ($siteDescription): ?>
            <p><?php echo nl2br(htmlspecialchars($siteDescription)); ?></p>
        <?php else: ?>
            <p>Här kan du berätta mer om dig själv eller ditt företag.</p>
            <p>Lägg till bilder, historik och annan relevant information.</p>
        <?php endif; ?>
    </section>
</main>

<?php
require_once 'includes/footer.php';
?>
