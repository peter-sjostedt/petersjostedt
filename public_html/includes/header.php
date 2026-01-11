<?php
require_once __DIR__ . '/config.php';

// Starta säker session och sätt headers
secure_session_start();
set_security_headers();

// Hämta inställningar från databasen
$settings = Settings::getInstance();
$siteName = $settings->get('site_name', SITE_NAME);
$siteDescription = $settings->get('site_description', '');
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <?php if ($siteDescription): ?>
    <meta name="description" content="<?php echo htmlspecialchars($siteDescription); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php"><?php echo htmlspecialchars($siteName); ?></a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Hem</a></li>
                <li><a href="about.php">Om oss</a></li>
                <li><a href="contact.php">Kontakt</a></li>
            </ul>
        </nav>
    </header>
