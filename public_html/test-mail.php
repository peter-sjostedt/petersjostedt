<?php
require_once 'includes/config.php';

echo "<pre>";

// Kolla att PHPMailer finns
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✓ PHPMailer är laddat\n";
} else {
    echo "✗ PHPMailer saknas!\n";
}

// Kolla att config laddas
$config = require __DIR__ . '/../config/mail.php';
echo "Host: " . $config['host'] . "\n";
echo "Port: " . $config['port'] . "\n";
echo "User: " . $config['username'] . "\n";
echo "Pass: " . (empty($config['password']) ? '(TOM!)' : '********') . "\n\n";

// Testa med debug på
$config['debug'] = 2;

$mailer = new Mailer();
$result = $mailer->send('peter.sjostedt@gmail.com', 'Testmail', '<h1>Hej!</h1><p>Det fungerar.</p>');

echo "\nResultat: " . ($result ? 'true' : 'false') . "\n";
echo "Fel: " . ($mailer->getLastError() ?? 'inget') . "\n";
echo "</pre>";