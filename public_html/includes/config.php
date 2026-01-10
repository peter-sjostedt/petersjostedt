<?php
/**
 * Konfigurationsfil
 * Känsliga uppgifter laddas från /config/ utanför public_html
 */

// Ladda Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Pdf.php';

// Ladda konfiguration från säker plats
$appConfig = require __DIR__ . '/../../config/app.php';
$dbConfig = require __DIR__ . '/../../config/database.php';

// Miljö
define('ENVIRONMENT', $appConfig['environment']);

// Sidinställningar
define('SITE_NAME', $appConfig['name']);
define('SITE_URL', $appConfig['url']);

// Databasinställningar
define('DB_HOST', $dbConfig['host']);
define('DB_NAME', $dbConfig['name']);
define('DB_USER', $dbConfig['user']);
define('DB_PASS', $dbConfig['pass']);
define('DB_CHARSET', $dbConfig['charset']);

// Tidzon
date_default_timezone_set($appConfig['timezone']);

// Felrapportering baserat på miljö
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../logs/error.log');
}

// Ladda säkerhetsfunktioner
require_once __DIR__ . '/security.php';

// Ladda kärnklasser
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Router.php';
require_once __DIR__ . '/../../src/Session.php';
require_once __DIR__ . '/../../src/User.php';
require_once __DIR__ . '/../../src/Settings.php';
require_once __DIR__ . '/../../src/Logger.php';
require_once __DIR__ . '/../../src/Language.php';
require_once __DIR__ . '/../../src/Mailer.php';