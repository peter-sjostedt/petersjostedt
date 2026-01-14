<?php
/**
 * Lägg till leverantörer och tvätterier som testdata
 * Kör: php database/seeds/seed_suppliers_laundry.php
 */

require_once __DIR__ . '/../../public_html/includes/config.php';

echo "=== Lägger till leverantörer och tvätterier ===\n\n";

$db = Database::getInstance()->getPdo();
$password = 'password123';
$hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3
]);

try {
    $db->beginTransaction();

    // ==================================================
    // Textilproducenter (supplier)
    // ==================================================
    echo "Skapar textilproducenter...\n";

    $suppliers = [
        ['SE556112-3344', 'Textilia AB', 'supplier', 'Industrigatan 45', '602 38', 'Norrköping', 'SE', '011-123 45 67', 'info@textilia.se', 1],
        ['SE556998-7766', 'MediTex Scandinavia', 'supplier', 'Väversgatan 12', '411 05', 'Göteborg', 'SE', '031-987 65 43', 'order@meditex.se', 1],
        ['DK12345678', 'Nordic Healthcare Textiles', 'supplier', 'Vesterbrogade 100', '1620', 'København', 'DK', '+45 33 12 34 56', 'sales@nht.dk', 1],
        ['SE556334-5566', 'Sjukvårdstextil Sverige', 'supplier', 'Fabriksvägen 8', '721 31', 'Västerås', 'SE', '021-456 78 90', 'kontakt@sjukvardstextil.se', 1],
    ];

    $stmt = $db->prepare("INSERT INTO organizations (id, name, org_type, address, postal_code, city, country, phone, email, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($suppliers as $org) {
        $stmt->execute($org);
        echo "  + {$org[1]} (Producent)\n";
    }

    // ==================================================
    // Tvätterier (laundry)
    // ==================================================
    echo "\nSkapar tvätterier...\n";

    $laundries = [
        ['SE556445-1122', 'Berendsen Textil Service', 'laundry', 'Tvättvägen 5', '169 70', 'Solna', 'SE', '08-555 123 00', 'info@berendsen.se', 1],
        ['SE556778-9900', 'Tvättman AB', 'laundry', 'Renhållningsvägen 22', '421 30', 'Västra Frölunda', 'SE', '031-712 00 00', 'order@tvattman.se', 1],
        ['SE556223-1199', 'CleanCare Medical', 'laundry', 'Sterilgatan 15', '212 35', 'Malmö', 'SE', '040-680 50 00', 'service@cleancare.se', 1],
    ];

    foreach ($laundries as $org) {
        $stmt->execute($org);
        echo "  + {$org[1]} (Tvätteri)\n";
    }

    // ==================================================
    // Enheter för leverantörer och tvätterier
    // ==================================================
    echo "\nSkapar enheter...\n";

    $units = [
        // Textilia AB
        ['SE556112-3344', 'Produktion Norrköping', 'api_textilia_prod_001'],
        ['SE556112-3344', 'Lager & Distribution', 'api_textilia_lager_002'],
        // MediTex
        ['SE556998-7766', 'Tillverkning', 'api_meditex_tillv_001'],
        // Berendsen
        ['SE556445-1122', 'Tvätteri Solna', 'api_berendsen_solna_001'],
        ['SE556445-1122', 'Tvätteri Göteborg', 'api_berendsen_gbg_002'],
        // Tvättman
        ['SE556778-9900', 'Huvudtvätteri', 'api_tvattman_main_001'],
        // CleanCare
        ['SE556223-1199', 'Steriltvätteri Malmö', 'api_cleancare_malmo_001'],
    ];

    $stmtUnit = $db->prepare("INSERT INTO units (organization_id, name, password, api_key, is_active) VALUES (?, ?, ?, ?, 1)");

    foreach ($units as $unit) {
        $stmtUnit->execute([$unit[0], $unit[1], $hashedPassword, $unit[2]]);
        echo "  + {$unit[1]}\n";
    }

    // ==================================================
    // Användare för leverantörer och tvätterier
    // ==================================================
    echo "\nSkapar användare...\n";

    $users = [
        // Leverantörer - org_admin
        ['admin@textilia.se', 'Anders Textil', 'org_admin', 'SE556112-3344'],
        ['chef@meditex.se', 'Maria Lindqvist', 'org_admin', 'SE556998-7766'],
        // Tvätterier - org_admin
        ['admin@berendsen.se', 'Björn Cleansson', 'org_admin', 'SE556445-1122'],
        ['chef@tvattman.se', 'Kerstin Tvätt', 'org_admin', 'SE556778-9900'],
        // Vanliga användare
        ['lager@textilia.se', 'Erik Lagerström', 'user', 'SE556112-3344'],
        ['produktion@berendsen.se', 'Lisa Produktsson', 'user', 'SE556445-1122'],
    ];

    $stmtUser = $db->prepare("INSERT INTO users (email, password, name, role, organization_id) VALUES (?, ?, ?, ?, ?)");

    foreach ($users as $user) {
        $stmtUser->execute([$user[0], $hashedPassword, $user[1], $user[2], $user[3]]);
        $roleLabel = $user[2] === 'org_admin' ? 'Org Admin' : 'User';
        echo "  + {$user[1]} ({$roleLabel})\n";
    }

    $db->commit();

    echo "\n=== Klart! ===\n\n";
    echo "Nya organisationer:\n";
    echo "  - 4 textilproducenter (supplier)\n";
    echo "  - 3 tvätterier (laundry)\n";
    echo "  - 7 enheter\n";
    echo "  - 6 användare\n\n";
    echo "Nya testkonton:\n";
    echo "  admin@textilia.se / password123 (Org Admin - Textilia AB)\n";
    echo "  chef@meditex.se / password123 (Org Admin - MediTex)\n";
    echo "  admin@berendsen.se / password123 (Org Admin - Berendsen)\n";
    echo "  chef@tvattman.se / password123 (Org Admin - Tvättman)\n\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "FEL: " . $e->getMessage() . "\n";
    exit(1);
}
