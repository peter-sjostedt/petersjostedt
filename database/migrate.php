#!/usr/bin/env php
<?php
/**
 * Database Migration CLI
 *
 * Användning:
 *   php database/migrate.php              - Kör alla pending migrations
 *   php database/migrate.php status       - Visa status för migrations
 *   php database/migrate.php rollback     - Rulla tillbaka senaste batch
 *   php database/migrate.php reset        - Rulla tillbaka alla migrations
 *   php database/migrate.php create <name> - Skapa ny migration
 */

// Endast CLI-körning
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Endast CLI-körning tillåten');
}

// Ladda konfiguration
require_once __DIR__ . '/../public_html/includes/config.php';

// Färgkoder för terminal
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RED', "\033[31m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");
define('BOLD', "\033[1m");

/**
 * Skriv färgad text
 */
function output(string $message, string $color = ''): void
{
    echo $color . $message . COLOR_RESET . PHP_EOL;
}

/**
 * Skriv framgångsmeddelande
 */
function success(string $message): void
{
    output('✓ ' . $message, COLOR_GREEN);
}

/**
 * Skriv varning
 */
function warning(string $message): void
{
    output('⚠ ' . $message, COLOR_YELLOW);
}

/**
 * Skriv fel
 */
function error(string $message): void
{
    output('✗ ' . $message, COLOR_RED);
}

/**
 * Skriv info
 */
function info(string $message): void
{
    output('ℹ ' . $message, COLOR_BLUE);
}

/**
 * Visa hjälptext
 */
function showHelp(): void
{
    echo BOLD . "Database Migration CLI" . COLOR_RESET . PHP_EOL;
    echo PHP_EOL;
    echo "Användning:" . PHP_EOL;
    echo "  php database/migrate.php [kommando]" . PHP_EOL;
    echo PHP_EOL;
    echo "Kommandon:" . PHP_EOL;
    echo "  (tom)            Kör alla pending migrations" . PHP_EOL;
    echo "  status           Visa status för alla migrations" . PHP_EOL;
    echo "  rollback         Rulla tillbaka senaste batch" . PHP_EOL;
    echo "  reset            Rulla tillbaka ALLA migrations (farligt!)" . PHP_EOL;
    echo "  create <name>    Skapa ny migration-fil" . PHP_EOL;
    echo "  help             Visa denna hjälptext" . PHP_EOL;
    echo PHP_EOL;
}

// Hämta kommando
$command = $argv[1] ?? 'migrate';
$migration = Migration::getInstance();

echo PHP_EOL;

switch ($command) {
    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;

    case 'status':
        info("Migration Status:");
        echo str_repeat('=', 80) . PHP_EOL;

        $status = $migration->status();

        if (empty($status)) {
            warning("Inga migrations hittades");
        } else {
            $maxLength = max(array_map(fn($s) => strlen($s['migration']), $status));

            foreach ($status as $item) {
                $name = str_pad($item['migration'], $maxLength + 2);

                if ($item['executed']) {
                    echo COLOR_GREEN . '✓' . COLOR_RESET . ' ';
                    echo $name;
                    echo COLOR_BLUE . " [Batch {$item['batch']}]" . COLOR_RESET;
                    echo " " . $item['executed_at'];
                } else {
                    echo COLOR_YELLOW . '○' . COLOR_RESET . ' ';
                    echo $name;
                    echo COLOR_YELLOW . " [Pending]" . COLOR_RESET;
                }

                echo PHP_EOL;
            }
        }

        echo str_repeat('=', 80) . PHP_EOL;
        break;

    case 'rollback':
        warning("Rullar tillbaka senaste batch...");
        echo PHP_EOL;

        $result = $migration->rollback();

        if (isset($result['message'])) {
            info($result['message']);
        } elseif ($result['success']) {
            foreach ($result['rolled_back'] as $name) {
                success("Återställd: {$name}");
            }
            echo PHP_EOL;
            success("Rollback genomförd!");
        } else {
            foreach ($result['errors'] as $err) {
                error("Fel i {$err['migration']}: {$err['error']}");
            }
            echo PHP_EOL;
            error("Rollback misslyckades!");
            exit(1);
        }
        break;

    case 'reset':
        error("VARNING: Detta rullar tillbaka ALLA migrations!");
        echo "Är du säker? Skriv 'yes' för att fortsätta: ";

        $handle = fopen('php://stdin', 'r');
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if ($confirmation !== 'yes') {
            warning("Avbruten.");
            break;
        }

        echo PHP_EOL;
        warning("Återställer alla migrations...");
        echo PHP_EOL;

        $result = $migration->reset();

        if ($result['success']) {
            foreach ($result['rolled_back'] as $name) {
                success("Återställd: {$name}");
            }
            echo PHP_EOL;
            success("Alla migrations återställda!");
        } else {
            foreach ($result['errors'] as $err) {
                error("Fel i {$err['migration']}: {$err['error']}");
            }
            echo PHP_EOL;
            error("Reset misslyckades!");
            exit(1);
        }
        break;

    case 'create':
        if (!isset($argv[2])) {
            error("Du måste ange ett namn för migrationen");
            echo "Exempel: php database/migrate.php create add_users_table" . PHP_EOL;
            exit(1);
        }

        $name = $argv[2];
        $filepath = $migration->create($name);

        success("Migration skapad: " . basename($filepath));
        info("Redigera filen: {$filepath}");
        break;

    case 'migrate':
    default:
        info("Kör migrations...");
        echo PHP_EOL;

        $result = $migration->migrate();

        if (isset($result['message'])) {
            info($result['message']);
        } elseif ($result['success']) {
            foreach ($result['executed'] as $name) {
                success("Körde: {$name}");
            }
            echo PHP_EOL;
            success("Migrations genomförda!");
        } else {
            foreach ($result['executed'] as $name) {
                success("Körde: {$name}");
            }
            foreach ($result['errors'] as $err) {
                error("Fel i {$err['migration']}: {$err['error']}");
            }
            echo PHP_EOL;
            error("Migrations misslyckades!");
            exit(1);
        }
        break;
}

echo PHP_EOL;
