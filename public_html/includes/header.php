<?php
require_once __DIR__ . '/config.php';

// Starta säker session och sätt headers
secure_session_start();
set_security_headers();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php"><?php echo SITE_NAME; ?></a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Hem</a></li>
                <li><a href="about.php">Om oss</a></li>
                <li><a href="contact.php">Kontakt</a></li>
            </ul>
        </nav>
    </header>
