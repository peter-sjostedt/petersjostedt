<?php
/**
 * Cron-jobb: Rotera loggfiler
 *
 * Komprimerar gamla loggfiler och raderar mycket gamla.
 * Kör dagligen via cron.
 *
 * Crontab exempel:
 * 0 2 * * * php /path/to/cron/rotate-logs.php
 */

// Endast CLI-körning
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Endast CLI-körning tillåten');
}

require_once __DIR__ . '/../public_html/includes/config.php';

echo "=== Loggrotation startar ===\n";
echo "Tid: " . date('Y-m-d H:i:s') . "\n\n";

$logger = Logger::getInstance();

// Rotera loggfiler
// - Komprimera loggar äldre än 7 dagar
// - Radera komprimerade loggar äldre än 90 dagar
echo "Roterar loggfiler...\n";
$rotateStats = $logger->rotateLogFiles(7, 90);

echo "Komprimerade: {$rotateStats['compressed']}\n";
echo "Raderade: {$rotateStats['deleted']}\n";

if (!empty($rotateStats['errors'])) {
    echo "\nFel uppstod:\n";
    foreach ($rotateStats['errors'] as $error) {
        echo "  - $error\n";
    }
}

// Rensa gamla databasloggar
// - Radera loggar äldre än 90 dagar från databas
echo "\nRensar gamla databasloggar...\n";
$cleaned = $logger->cleanOldLogs(90);
echo "Raderade från databas: $cleaned poster\n";

// Visa aktuella loggfiler
echo "\nAktuella loggfiler:\n";
$logFiles = $logger->getLogFiles(true);
foreach ($logFiles as $file) {
    echo "  {$file['name']}: {$file['size_human']} ({$file['modified_human']})\n";
}

echo "\n=== Loggrotation slutförd ===\n";
