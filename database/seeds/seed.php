<?php
/**
 * Database Seeder - Skapa testdata
 * Kör: php database/seeds/seed.php
 */

require_once __DIR__ . '/../../public_html/includes/config.php';

echo "=== Hospitex Database Seeder ===\n\n";

$db = Database::getInstance()->getPdo();
$password = 'password123';
$hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3
]);

echo "Lösenord för alla testanvändare: {$password}\n";
echo "Hash: " . substr($hashedPassword, 0, 50) . "...\n\n";

// Kontrollera om det redan finns testdata
$existingOrgs = $db->query("SELECT COUNT(*) FROM organizations WHERE id != 'SYSTEM'")->fetchColumn();
if ($existingOrgs > 0) {
    echo "Det finns redan {$existingOrgs} organisationer i databasen.\n";
    echo "Vill du rensa och skapa ny testdata? (j/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'j') {
        echo "Avbryter.\n";
        exit;
    }
    fclose($handle);

    // Rensa testdata
    echo "\nRensar befintlig testdata...\n";
    $db->exec("DELETE FROM users WHERE organization_id IS NOT NULL");
    $db->exec("DELETE FROM units");
    $db->exec("DELETE FROM organizations WHERE id != 'SYSTEM'");
    echo "Klart!\n\n";
}

try {
    $db->beginTransaction();

    // ==================================================
    // Organisationer
    // ==================================================
    echo "Skapar organisationer...\n";

    $organizations = [
        ['SE556677-8899', 'Karolinska Universitetssjukhuset', 'customer', 'Eugeniavägen 3', '171 76', 'Solna', 'SE', '08-517 700 00', 'info@karolinska.se', 1, '[{"label":"Artikelnamn","type":"text","required":true},{"label":"Storlek","type":"select","options":["XS","S","M","L","XL"]},{"label":"Material","type":"text"}]'],
        ['SE556011-2233', 'Sahlgrenska Universitetssjukhuset', 'customer', 'Per Dubbsgatan 15', '413 45', 'Göteborg', 'SE', '031-342 10 00', 'info@sahlgrenska.se', 1, '[{"label":"Artikelnamn","type":"text","required":true},{"label":"Avdelning","type":"text"}]'],
        ['SE556445-6677', 'Skånes Universitetssjukhus', 'customer', 'Jan Waldenströms gata 35', '205 02', 'Malmö', 'SE', '040-33 10 00', 'info@sus.se', 1, null],
        ['SE556889-0011', 'Akademiska Sjukhuset', 'customer', 'Sjukhusvägen 1', '752 37', 'Uppsala', 'SE', '018-611 00 00', 'info@akademiska.se', 1, null],
        ['SE556223-4455', 'Norrlands Universitetssjukhus', 'customer', 'Norrlandsvägen 10', '901 85', 'Umeå', 'SE', '090-785 00 00', 'info@nus.se', 0, null],
    ];

    $stmt = $db->prepare("INSERT INTO organizations (id, name, org_type, address, postal_code, city, country, phone, email, is_active, article_schema) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($organizations as $org) {
        $stmt->execute($org);
        echo "  + {$org[1]}\n";
    }

    // ==================================================
    // Enheter
    // ==================================================
    echo "\nSkapar enheter...\n";

    $units = [
        // Karolinska
        ['SE556677-8899', 'Sterilcentralen', 'api_karolinska_steril_001'],
        ['SE556677-8899', 'Kirurgi A', 'api_karolinska_kirurgia_002'],
        ['SE556677-8899', 'Kirurgi B', 'api_karolinska_kirurgib_003'],
        ['SE556677-8899', 'Akutmottagningen', 'api_karolinska_akut_004'],
        ['SE556677-8899', 'IVA', 'api_karolinska_iva_005'],
        // Sahlgrenska
        ['SE556011-2233', 'Sterilcentralen SU', 'api_sahlgrenska_steril_001'],
        ['SE556011-2233', 'Ortopedi', 'api_sahlgrenska_ortopedi_002'],
        ['SE556011-2233', 'Kardiologi', 'api_sahlgrenska_kardio_003'],
        // Skåne
        ['SE556445-6677', 'Sterilcentral Malmö', 'api_sus_steril_001'],
        ['SE556445-6677', 'Operation Lund', 'api_sus_op_lund_002'],
        // Akademiska
        ['SE556889-0011', 'Sterilcentralen Uppsala', 'api_akademiska_steril_001'],
    ];

    $stmt = $db->prepare("INSERT INTO units (organization_id, name, password, api_key, is_active) VALUES (?, ?, ?, ?, 1)");

    foreach ($units as $unit) {
        $stmt->execute([$unit[0], $unit[1], $hashedPassword, $unit[2]]);
        echo "  + {$unit[1]}\n";
    }

    // ==================================================
    // Användare
    // ==================================================
    echo "\nSkapar användare...\n";

    $users = [
        // Org admins
        ['admin@karolinska.se', 'Anna Karlsson', 'org_admin', 'SE556677-8899'],
        ['chef@sahlgrenska.se', 'Erik Lindberg', 'org_admin', 'SE556011-2233'],
        ['admin@sus.se', 'Maria Svensson', 'org_admin', 'SE556445-6677'],
        // Regular users
        ['lars.berg@karolinska.se', 'Lars Berg', 'user', 'SE556677-8899'],
        ['eva.holm@karolinska.se', 'Eva Holm', 'user', 'SE556677-8899'],
        ['johan.nyman@sahlgrenska.se', 'Johan Nyman', 'user', 'SE556011-2233'],
        ['karin.lund@sus.se', 'Karin Lund', 'user', 'SE556445-6677'],
        ['per.strom@akademiska.se', 'Per Ström', 'user', 'SE556889-0011'],
    ];

    $stmt = $db->prepare("INSERT INTO users (email, password, name, role, organization_id) VALUES (?, ?, ?, ?, ?)");

    foreach ($users as $user) {
        $stmt->execute([$user[0], $hashedPassword, $user[1], $user[2], $user[3]]);
        $roleLabel = $user[2] === 'org_admin' ? 'Org Admin' : 'User';
        echo "  + {$user[1]} ({$roleLabel})\n";
    }

    $db->commit();

    echo "\n=== Testdata skapad! ===\n\n";
    echo "Sammanfattning:\n";
    echo "  - 5 organisationer (4 aktiva, 1 inaktiv)\n";
    echo "  - 11 enheter\n";
    echo "  - 8 användare (3 org_admin, 5 user)\n\n";
    echo "Testkonton:\n";
    echo "  admin@karolinska.se / password123 (Org Admin - Karolinska)\n";
    echo "  chef@sahlgrenska.se / password123 (Org Admin - Sahlgrenska)\n";
    echo "  admin@sus.se / password123 (Org Admin - Skåne)\n";
    echo "  lars.berg@karolinska.se / password123 (User - Karolinska)\n\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "FEL: " . $e->getMessage() . "\n";
    exit(1);
}
