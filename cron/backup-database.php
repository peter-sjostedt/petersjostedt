<?php
/**
 * Cron-jobb: Databasbackup
 *
 * Skapar daglig databasbackup och roterar gamla backuper.
 * Kör dagligen via cron.
 *
 * Crontab exempel:
 * 0 3 * * * php /path/to/cron/backup-database.php
 */

// Endast CLI-körning
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Endast CLI-körning tillåten');
}

require_once __DIR__ . '/../public_html/includes/config.php';

echo "=== Databasbackup startar ===\n";
echo "Tid: " . date('Y-m-d H:i:s') . "\n\n";

$backup = Backup::getInstance();

// 1. Skapa daglig backup
echo "Skapar daglig backup...\n";
$result = $backup->createBackup('daily');

if ($result['success']) {
    echo "✓ Backup skapad: {$result['filename']}\n";
    echo "  Storlek: {$result['size_human']}\n";
    echo "  Tid: {$result['duration']}s\n";
} else {
    echo "✗ Backup misslyckades: {$result['error']}\n";
    exit(1);
}

// 2. Rotera backuper
echo "\nRoterar backuper...\n";
$rotateStats = $backup->rotateBackups();

if ($rotateStats['promoted'] > 0) {
    echo "✓ Befordrade backuper: {$rotateStats['promoted']}\n";
}

if ($rotateStats['deleted'] > 0) {
    echo "✓ Raderade gamla backuper: {$rotateStats['deleted']}\n";
}

if (!empty($rotateStats['errors'])) {
    echo "Fel uppstod:\n";
    foreach ($rotateStats['errors'] as $error) {
        echo "  - $error\n";
    }
}

// 3. Visa statistik
echo "\nBackup-statistik:\n";
$stats = $backup->getStats();

echo "  Totalt antal backuper: {$stats['total']}\n";
echo "  - Dagliga: {$stats['daily']}\n";
echo "  - Veckovisa: {$stats['weekly']}\n";
echo "  - Månatliga: {$stats['monthly']}\n";

if (isset($stats['total_size_human'])) {
    echo "  Total storlek: {$stats['total_size_human']}\n";
    echo "  Äldsta backup: {$stats['oldest']}\n";
    echo "  Senaste backup: {$stats['newest']}\n";
}

// 4. Verifiera senaste backup
echo "\nVerifierar senaste backup...\n";
if ($result['success']) {
    $verify = $backup->verifyBackup($result['path']);

    if ($verify['valid']) {
        echo "✓ Backup är giltig\n";
        echo "  Tabeller: {$verify['info']['tables']}\n";
        echo "  INSERT-satser: {$verify['info']['inserts']}\n";
        echo "  Okomprimerad storlek: {$verify['info']['uncompressed_size_human']}\n";
        echo "  Kompressionsgrad: {$verify['info']['compression_ratio']}\n";
    } else {
        echo "✗ Backup-verifiering misslyckades: {$verify['error']}\n";
        exit(1);
    }
}

echo "\n=== Databasbackup slutförd ===\n";
