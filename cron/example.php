<?php
/**
 * Exempel på cron-jobb
 *
 * Kör via CLI:
 *   php /path/to/petersjostedt/cron/example.php
 *
 * Lägg till i crontab (Linux) eller Task Scheduler (Windows):
 *   # Kör varje timme
 *   0 * * * * /usr/bin/php /var/www/petersjostedt/cron/example.php >> /var/log/cron.log 2>&1
 *
 * Säkerhet:
 * - Kontrollerar att scriptet körs via CLI (inte webb)
 * - Laddar konfiguration från säker plats
 * - Loggar all aktivitet
 */

// Säkerhetskontroll: Endast CLI-körning tillåten
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Åtkomst nekad. Detta script kan endast köras via CLI.');
}

// Definiera rotsökväg
define('ROOT_PATH', dirname(__DIR__));

// Ladda konfiguration
$appConfig = require ROOT_PATH . '/config/app.php';
$dbConfig = require ROOT_PATH . '/config/database.php';

// Sätt konstanter
define('ENVIRONMENT', $appConfig['environment']);
define('DB_HOST', $dbConfig['host']);
define('DB_NAME', $dbConfig['name']);
define('DB_USER', $dbConfig['user']);
define('DB_PASS', $dbConfig['pass']);
define('DB_CHARSET', $dbConfig['charset']);

// Tidzon
date_default_timezone_set($appConfig['timezone']);

// Ladda klasser
require_once ROOT_PATH . '/src/Database.php';

/**
 * Loggfunktion för cron-jobb
 */
function cronLog(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;

    // Skriv till loggfil
    $logFile = ROOT_PATH . '/logs/cron.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

    // Skriv även till stdout för CLI
    echo $logMessage;
}

// =====================================================
// CRON-JOBBETS HUVUDLOGIK - ÄNDRA NEDAN
// =====================================================

cronLog('Cron-jobb startar...');

try {
    // Exempel: Hämta databaskoppling
    $db = Database::getInstance();

    // Exempel: Rensa gamla sessioner eller loggar
    // $db->execute("DELETE FROM sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");

    // Exempel: Skicka påminnelser
    // $users = $db->fetchAll("SELECT * FROM users WHERE reminder_date = CURDATE()");
    // foreach ($users as $user) {
    //     sendReminder($user);
    // }

    // Exempel: Generera rapport
    // $stats = $db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    // cronLog("Dagens ordrar: " . $stats['total']);

    cronLog('Cron-jobb slutfört framgångsrikt');

} catch (Exception $e) {
    cronLog('FEL: ' . $e->getMessage());
    exit(1); // Avsluta med felkod
}

exit(0); // Avsluta med framgångskod
