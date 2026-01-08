<?php
/**
 * Setup admin-användare
 * TA BORT FILEN DIREKT EFTER ANVÄNDNING
 */

header('Content-Type: text/plain; charset=utf-8');

// Hitta rätt sökväg till config
if (file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
} else {
    require_once __DIR__ . '/../public_html/includes/config.php';
}

// ==========================================
// ÄNDRA DESSA UPPGIFTER
// ==========================================
$email = 'admin@petersjostedt.se';
$password = 'admin!';
$name = 'Admin';
$role = 'admin';
// ==========================================

echo "Setup admin-användare\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance();
    echo "✓ Databasanslutning OK\n";
    
    $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        die("✗ Användaren $email finns redan (ID: {$existing['id']})\n");
    }
    echo "✓ E-posten är ledig\n";
    
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
    echo "✓ Lösenord hashat\n";
    
    $sql = "INSERT INTO users (email, password, name, role, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())";
    
    $result = $db->execute($sql, [$email, $hashedPassword, $name, $role]);
    
    if ($result) {
        $newUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        
        echo "\n========================================\n";
        echo "✓ Admin skapad!\n";
        echo "ID: " . ($newUser['id'] ?? 'okänt') . "\n";
        echo "E-post: $email\n";
        echo "========================================\n";
        echo "\n⚠️  TA BORT DENNA FIL NU!\n";
    } else {
        echo "\n✗ Kunde inte skapa användare.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fel: " . $e->getMessage() . "\n";
}