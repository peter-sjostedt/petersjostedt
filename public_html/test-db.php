<?php
require_once 'includes/config.php';

echo "<h1>Systemtest</h1>";
echo "<pre>";

// Testa databasanslutning
try {
    $db = Database::getInstance();
    echo "✓ Database: Anslutning OK\n";
} catch (Exception $e) {
    echo "✗ Database: " . $e->getMessage() . "\n";
}

// Testa User-klassen
try {
    $user = new User();
    $count = $user->count();
    echo "✓ User: {$count} användare i databasen\n";
} catch (Exception $e) {
    echo "✗ User: " . $e->getMessage() . "\n";
}

// Testa Settings-klassen
try {
    $settings = Settings::getInstance();
    $settings->set('test_key', 'test_value');
    $value = $settings->get('test_key');
    $settings->delete('test_key');
    echo "✓ Settings: Läs/skriv fungerar\n";
} catch (Exception $e) {
    echo "✗ Settings: " . $e->getMessage() . "\n";
}

// Testa Logger-klassen
try {
    Logger::write('SYSTEM_TEST', null, 'Automatiskt test');
    echo "✓ Logger: Loggning fungerar\n";
} catch (Exception $e) {
    echo "✗ Logger: " . $e->getMessage() . "\n";
}

// Testa Session-klassen
try {
    Session::start();
    Session::set('test', 'value');
    $val = Session::get('test');
    Session::remove('test');
    echo "✓ Session: Fungerar\n";
} catch (Exception $e) {
    echo "✗ Session: " . $e->getMessage() . "\n";
}

echo "\n--- Miljöinfo ---\n";
echo "Miljö: " . ENVIRONMENT . "\n";
echo "PHP: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Okänd') . "\n";

echo "</pre>";
