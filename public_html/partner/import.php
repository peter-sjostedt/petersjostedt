<?php
/**
 * Partner Portal - Import av artiklar/försändelser från CSV
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

if (!Session::isLoggedIn() || !Session::isOrgAdmin()) {
    header('Location: login.php');
    exit;
}

$organizationId = Session::getOrganizationId();
$userId = Session::getUserId();

if (!$organizationId) {
    Session::flash('error', t('error.unauthorized'));
    header('Location: index.php');
    exit;
}

$type = $_GET['type'] ?? 'articles';
$tab = $_GET['tab'] ?? 'outgoing';
if (!in_array($type, ['articles', 'templates', 'shipments'])) {
    $type = 'articles';
}

// Hämta organisation med article_schema
$orgModel = new Organization();
$organization = $orgModel->findById($organizationId);

$articleSchema = [];
if (!empty($organization['article_schema'])) {
    if (is_string($organization['article_schema'])) {
        $articleSchema = json_decode($organization['article_schema'], true) ?: [];
    } else {
        $articleSchema = $organization['article_schema'];
    }
    usort($articleSchema, fn($a, $b) => ((int)($a['sort_order'] ?? 0)) - ((int)($b['sort_order'] ?? 0)));
}

function fieldKey(string $label): string
{
    $key = mb_strtolower($label);
    $key = str_replace(['å', 'ä', 'ö'], ['a', 'a', 'o'], $key);
    $key = preg_replace('/[^a-z0-9]/', '_', $key);
    $key = preg_replace('/_+/', '_', $key);
    return trim($key, '_');
}

/**
 * Generera SKU baserat på fältvärdena
 */
function generateSku(PDO $db, string $orgId, array $fieldData): string
{
    $parts = [];
    foreach ($fieldData as $value) {
        if (!empty($value)) {
            $clean = preg_replace('/[^a-zA-ZåäöÅÄÖ0-9]/', '', $value);
            $clean = mb_strtoupper(mb_substr($clean, 0, 3));
            if (!empty($clean)) {
                $parts[] = $clean;
            }
        }
    }

    $baseSku = !empty($parts) ? implode('-', $parts) : 'ART';

    // Kolla om SKU redan finns, lägg till suffix om så
    $sku = $baseSku;
    $counter = 1;
    while (true) {
        $stmt = $db->prepare("SELECT id FROM articles WHERE organization_id = ? AND sku = ?");
        $stmt->execute([$orgId, $sku]);
        if (!$stmt->fetch()) {
            break;
        }
        $counter++;
        $sku = $baseSku . '-' . $counter;
    }

    return $sku;
}

$db = Database::getInstance()->getPdo();
$result = null;

// Hämta enheter för templates/shipments import
$units = [];
if ($type === 'templates' || $type === 'shipments') {
    $stmt = $db->prepare("SELECT id, name FROM units WHERE organization_id = ? AND is_active = 1 ORDER BY name");
    $stmt->execute([$organizationId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        $units[$u['id']] = $u['name'];
        $units[mb_strtolower($u['name'])] = $u['id']; // Lookup by name
    }
}

// Hämta relaterade organisationer för shipments import
$relatedOrgs = [];
if ($type === 'shipments') {
    $relationType = $tab === 'outgoing' ? 'customer' : 'supplier';
    $stmt = $db->prepare("
        SELECT o.id, o.name
        FROM organizations o
        INNER JOIN organization_relations r ON r.partner_org_id = o.id
        WHERE r.organization_id = ? AND r.relation_type = ? AND r.is_active = 1
        ORDER BY o.name
    ");
    $stmt->execute([$organizationId, $relationType]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
        $relatedOrgs[$o['id']] = $o['name'];
        $relatedOrgs[mb_strtolower($o['name'])] = $o['id']; // Lookup by name
    }
}

// Hämta händelsetyper för templates import
$eventTypes = [];
if ($type === 'templates') {
    $eventTypeModel = new EventType();
    foreach ($eventTypeModel->getAll() as $et) {
        $eventTypes[$et['id']] = $et;
        $eventTypes[$et['code']] = $et['id']; // Lookup by code
        $eventTypes[mb_strtolower($et['name_sv'])] = $et['id']; // Lookup by Swedish name
        $eventTypes[mb_strtolower($et['name_en'])] = $et['id']; // Lookup by English name
    }
}

// Hantera uppladdning
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $result = ['success' => false, 'error' => t('error.csrf')];
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $result = ['success' => false, 'error' => 'Ingen fil uppladdad eller fel vid uppladdning'];
    } else {
        if ($type === 'articles') {
            $result = processArticleImport($_FILES['csv_file']['tmp_name'], $organizationId, $articleSchema, $db);
        } elseif ($type === 'shipments') {
            $result = processShipmentImport($_FILES['csv_file']['tmp_name'], $organizationId, $relatedOrgs, $units, $userId, $tab, $db);
        } else {
            $result = processTemplateImport($_FILES['csv_file']['tmp_name'], $organizationId, $eventTypes, $units, $userId, $db);
        }
    }
}

/**
 * Bearbeta artikel CSV-import
 */
function processArticleImport(string $filePath, string $orgId, array $articleSchema, PDO $db): array
{
    $content = file_get_contents($filePath);

    // Ta bort BOM om den finns
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    // Detektera separator genom att testa vilken som ger fler kolumner i rubrikraden
    $firstLine = strtok($content, "\n");
    $colsWithComma = count(str_getcsv($firstLine, ','));
    $colsWithSemicolon = count(str_getcsv($firstLine, ';'));
    $separator = ($colsWithSemicolon > $colsWithComma) ? ';' : ',';

    // Parsa CSV
    $lines = array_filter(explode("\n", $content), 'trim');
    if (count($lines) < 2) {
        return ['success' => false, 'error' => 'Filen innehåller ingen data'];
    }

    // Parsa rubrikrad
    $headers = str_getcsv(array_shift($lines), $separator);
    $headers = array_map('trim', $headers);

    // Hitta SKU-kolumn
    $skuIndex = array_search('SKU', $headers);
    if ($skuIndex === false) {
        return ['success' => false, 'error' => 'Kolumnen "SKU" saknas i filen'];
    }

    // Mappa rubriker till fältnycklar
    $fieldMap = [];
    foreach ($articleSchema as $field) {
        $headerIndex = array_search($field['label'], $headers);
        if ($headerIndex !== false) {
            $fieldMap[$headerIndex] = fieldKey($field['label']);
        }
    }

    $created = 0;
    $updated = 0;
    $errors = [];
    $rowNum = 1;

    foreach ($lines as $line) {
        $rowNum++;
        $line = trim($line);
        if (empty($line)) continue;

        $values = str_getcsv($line, $separator);
        $sku = trim($values[$skuIndex] ?? '');

        // Bygg fältdata
        $fieldData = [];
        foreach ($fieldMap as $csvIndex => $fieldKey) {
            $fieldData[$fieldKey] = trim($values[$csvIndex] ?? '');
        }

        // Generera SKU om den saknas
        if (empty($sku)) {
            $sku = generateSku($db, $orgId, $fieldData);
        }

        $dataJson = json_encode($fieldData, JSON_UNESCAPED_UNICODE);

        // Kolla om artikeln redan finns
        $stmt = $db->prepare("SELECT id FROM articles WHERE organization_id = ? AND sku = ?");
        $stmt->execute([$orgId, $sku]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        // Generera namn från första fältvärdet eller SKU
        $name = '';
        foreach ($fieldData as $value) {
            if (!empty($value)) {
                $name = $value;
                break;
            }
        }
        if (empty($name)) {
            $name = $sku;
        }

        if ($existing) {
            // Uppdatera
            $stmt = $db->prepare("UPDATE articles SET name = ?, data = ? WHERE id = ?");
            $stmt->execute([$name, $dataJson, $existing['id']]);
            $updated++;
        } else {
            // Skapa ny
            $stmt = $db->prepare("INSERT INTO articles (organization_id, sku, name, data, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$orgId, $sku, $name, $dataJson]);
            $created++;
        }
    }

    return [
        'success' => true,
        'created' => $created,
        'updated' => $updated,
        'errors' => $errors
    ];
}

/**
 * Bearbeta template CSV-import
 */
function processTemplateImport(string $filePath, string $orgId, array $eventTypes, array $units, int $userId, PDO $db): array
{
    $content = file_get_contents($filePath);
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    $firstLine = strtok($content, "\n");
    $colsWithComma = count(str_getcsv($firstLine, ','));
    $colsWithSemicolon = count(str_getcsv($firstLine, ';'));
    $separator = ($colsWithSemicolon > $colsWithComma) ? ';' : ',';

    $lines = array_filter(explode("\n", $content), 'trim');
    if (count($lines) < 2) {
        return ['success' => false, 'error' => 'Filen innehåller ingen data'];
    }

    $headers = str_getcsv(array_shift($lines), $separator);
    $headers = array_map('trim', $headers);

    // Hitta kolumnindex
    $labelCol = findColumn($headers, ['Etikett', 'Label', 'label']);
    $eventTypeCol = findColumn($headers, ['Händelsetyp', 'Event Type', 'event_type']);
    $unitCol = findColumn($headers, ['Enhet', 'Unit', 'unit']);
    $targetUnitCol = findColumn($headers, ['Målenhet', 'Target Unit', 'target_unit']);
    $reusableCol = findColumn($headers, ['Återanvändbar', 'Reusable', 'is_reusable']);
    $notesCol = findColumn($headers, ['Anteckningar', 'Notes', 'notes']);

    // Måste ha etikett och händelsetyp
    if ($labelCol === false) {
        return ['success' => false, 'error' => 'Kolumnen "Etikett" saknas i filen'];
    }
    if ($eventTypeCol === false) {
        return ['success' => false, 'error' => 'Kolumnen "Händelsetyp" saknas i filen'];
    }

    // Hämta befintliga mallar för att kunna uppdatera baserat på etikett
    $templateModel = new EventTemplate();
    $existingTemplates = [];
    foreach ($templateModel->findByOrganization($orgId) as $t) {
        $existingTemplates[mb_strtolower($t['label'])] = $t;
    }

    $created = 0;
    $updated = 0;
    $errors = [];
    $rowNum = 1;

    foreach ($lines as $line) {
        $rowNum++;
        $line = trim($line);
        if (empty($line)) continue;

        $values = str_getcsv($line, $separator);

        $label = trim($values[$labelCol] ?? '');
        $eventTypeValue = trim($values[$eventTypeCol] ?? '');
        $unitValue = $unitCol !== false ? trim($values[$unitCol] ?? '') : '';
        $targetUnitValue = $targetUnitCol !== false ? trim($values[$targetUnitCol] ?? '') : '';
        $reusableValue = $reusableCol !== false ? trim($values[$reusableCol] ?? '') : '';
        $notes = $notesCol !== false ? trim($values[$notesCol] ?? '') : '';

        // Validera etikett
        if (empty($label)) {
            $errors[] = "Rad {$rowNum}: Etikett saknas";
            continue;
        }

        // Hitta händelsetyp-ID
        $eventTypeId = null;
        if (is_numeric($eventTypeValue) && isset($eventTypes[(int)$eventTypeValue])) {
            $eventTypeId = (int)$eventTypeValue;
        } elseif (isset($eventTypes[$eventTypeValue])) {
            $eventTypeId = $eventTypes[$eventTypeValue];
        } elseif (isset($eventTypes[mb_strtolower($eventTypeValue)])) {
            $eventTypeId = $eventTypes[mb_strtolower($eventTypeValue)];
        }

        if (!$eventTypeId) {
            $errors[] = "Rad {$rowNum}: Ogiltig händelsetyp '{$eventTypeValue}'";
            continue;
        }

        // Hitta enhets-ID
        $unitId = null;
        if (!empty($unitValue)) {
            if (is_numeric($unitValue) && isset($units[(int)$unitValue])) {
                $unitId = (int)$unitValue;
            } elseif (isset($units[mb_strtolower($unitValue)])) {
                $unitId = $units[mb_strtolower($unitValue)];
            }
        }

        // Hitta målenhet-ID
        $targetUnitId = null;
        if (!empty($targetUnitValue)) {
            if (is_numeric($targetUnitValue) && isset($units[(int)$targetUnitValue])) {
                $targetUnitId = (int)$targetUnitValue;
            } elseif (isset($units[mb_strtolower($targetUnitValue)])) {
                $targetUnitId = $units[mb_strtolower($targetUnitValue)];
            }
        }

        // Avgör is_reusable
        $isReusable = 1; // Default true
        if (!empty($reusableValue)) {
            $lower = mb_strtolower($reusableValue);
            if (in_array($lower, ['nej', 'no', 'false', '0', 'engångs', 'one-time'])) {
                $isReusable = 0;
            }
        }

        // Kolla om mallen redan finns (case-insensitive)
        $labelKey = mb_strtolower($label);
        $existing = $existingTemplates[$labelKey] ?? null;

        if ($existing) {
            // Uppdatera befintlig mall
            $stmt = $db->prepare("UPDATE event_templates SET event_type_id = ?, unit_id = ?, target_unit_id = ?, is_reusable = ?, notes = ? WHERE id = ?");
            $stmt->execute([$eventTypeId, $unitId, $targetUnitId, $isReusable, $notes ?: null, $existing['id']]);
            $updated++;
        } else {
            // Skapa ny mall
            $newId = $templateModel->create($orgId, $eventTypeId, $label, [
                'unit_id' => $unitId,
                'target_unit_id' => $targetUnitId,
                'is_reusable' => $isReusable,
                'notes' => $notes ?: null,
                'created_by_user_id' => $userId
            ]);

            if ($newId) {
                $created++;
                // Lägg till i existingTemplates så att dubletter i samma fil inte skapas
                $existingTemplates[$labelKey] = ['id' => $newId, 'label' => $label];
            } else {
                $errors[] = "Rad {$rowNum}: Kunde inte skapa mall";
            }
        }
    }

    return [
        'success' => true,
        'created' => $created,
        'updated' => $updated,
        'errors' => $errors
    ];
}

/**
 * Bearbeta shipment CSV-import
 */
function processShipmentImport(string $filePath, string $orgId, array $relatedOrgs, array $units, int $userId, string $tab, PDO $db): array
{
    $content = file_get_contents($filePath);
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    $firstLine = strtok($content, "\n");
    $colsWithComma = count(str_getcsv($firstLine, ','));
    $colsWithSemicolon = count(str_getcsv($firstLine, ';'));
    $separator = ($colsWithSemicolon > $colsWithComma) ? ';' : ',';

    $lines = array_filter(explode("\n", $content), 'trim');
    if (count($lines) < 2) {
        return ['success' => false, 'error' => 'Filen innehåller ingen data'];
    }

    $headers = str_getcsv(array_shift($lines), $separator);
    $headers = array_map('trim', $headers);

    // Hitta kolumnindex för partner (namn)
    $partnerCol = $tab === 'outgoing'
        ? findColumn($headers, ['Mottagare', 'Kund', 'Customer', 'Recipient', 'to_org'])
        : findColumn($headers, ['Leverantör', 'Supplier', 'from_org']);

    // Hitta kolumnindex för partner-ID (prioriteras om den finns)
    $partnerIdCol = $tab === 'outgoing'
        ? findColumn($headers, ['to_org_id'])
        : findColumn($headers, ['from_org_id']);

    $salesOrderCol = findColumn($headers, ['Försäljningsorder', 'Sales Order', 'SO', 'sales_order_id']);
    $purchaseOrderCol = findColumn($headers, ['Inköpsorder', 'Purchase Order', 'PO', 'purchase_order_id']);

    // Hitta kolumnindex för enhet (namn)
    $unitCol = $tab === 'outgoing'
        ? findColumn($headers, ['Från enhet', 'From Unit', 'from_unit'])
        : findColumn($headers, ['Till enhet', 'To Unit', 'to_unit']);

    // Hitta kolumnindex för enhets-ID (prioriteras om den finns)
    $unitIdCol = $tab === 'outgoing'
        ? findColumn($headers, ['from_unit_id'])
        : findColumn($headers, ['to_unit_id']);

    $notesCol = findColumn($headers, ['Anteckningar', 'Notes', 'notes']);

    // Partner-kolumn eller partner-ID-kolumn krävs
    if ($partnerCol === false && $partnerIdCol === false) {
        $colName = $tab === 'outgoing' ? 'Mottagare/Kund eller to_org_id' : 'Leverantör eller from_org_id';
        return ['success' => false, 'error' => "Kolumnen \"{$colName}\" saknas i filen"];
    }

    $shipmentModel = new Shipment();

    $created = 0;
    $errors = [];
    $rowNum = 1;

    foreach ($lines as $line) {
        $rowNum++;
        $line = trim($line);
        if (empty($line)) continue;

        $values = str_getcsv($line, $separator);

        // Hämta värden
        $partnerIdValue = $partnerIdCol !== false ? trim($values[$partnerIdCol] ?? '') : '';
        $partnerValue = $partnerCol !== false ? trim($values[$partnerCol] ?? '') : '';
        $salesOrderId = $salesOrderCol !== false ? trim($values[$salesOrderCol] ?? '') : '';
        $purchaseOrderId = $purchaseOrderCol !== false ? trim($values[$purchaseOrderCol] ?? '') : '';
        $unitIdValue = $unitIdCol !== false ? trim($values[$unitIdCol] ?? '') : '';
        $unitValue = $unitCol !== false ? trim($values[$unitCol] ?? '') : '';
        $notes = $notesCol !== false ? trim($values[$notesCol] ?? '') : '';

        // Hitta partner-ID (prioritera ID-kolumnen)
        $partnerId = null;
        if (!empty($partnerIdValue) && isset($relatedOrgs[$partnerIdValue])) {
            $partnerId = $partnerIdValue;
        } elseif (!empty($partnerValue)) {
            if (isset($relatedOrgs[$partnerValue])) {
                $partnerId = $partnerValue; // Direkt ID i namnkolumnen
            } elseif (isset($relatedOrgs[mb_strtolower($partnerValue)])) {
                $partnerId = $relatedOrgs[mb_strtolower($partnerValue)];
            }
        }

        if (!$partnerId) {
            $partnerType = $tab === 'outgoing' ? 'kund' : 'leverantör';
            $displayValue = !empty($partnerIdValue) ? $partnerIdValue : $partnerValue;
            $errors[] = "Rad {$rowNum}: Okänd {$partnerType} '{$displayValue}'";
            continue;
        }

        // Hitta enhets-ID (prioritera ID-kolumnen)
        $unitId = null;
        if (!empty($unitIdValue) && is_numeric($unitIdValue) && isset($units[(int)$unitIdValue])) {
            $unitId = (int)$unitIdValue;
        } elseif (!empty($unitValue)) {
            if (is_numeric($unitValue) && isset($units[(int)$unitValue])) {
                $unitId = (int)$unitValue;
            } elseif (isset($units[mb_strtolower($unitValue)])) {
                $unitId = $units[mb_strtolower($unitValue)];
            }
        }

        // Skapa försändelse
        if ($tab === 'outgoing') {
            $fromOrgId = $orgId;
            $toOrgId = $partnerId;
            $options = [
                'sales_order_id' => $salesOrderId ?: null,
                'purchase_order_id' => $purchaseOrderId ?: null,
                'from_unit_id' => $unitId,
                'notes' => $notes ?: null,
                'created_by_user_id' => $userId
            ];
        } else {
            $fromOrgId = $partnerId;
            $toOrgId = $orgId;
            $options = [
                'sales_order_id' => $salesOrderId ?: null,
                'purchase_order_id' => $purchaseOrderId ?: null,
                'to_unit_id' => $unitId,
                'notes' => $notes ?: null,
                'created_by_user_id' => $userId
            ];
        }

        $newId = $shipmentModel->create($fromOrgId, $toOrgId, $options);

        if ($newId) {
            $created++;
        } else {
            $errors[] = "Rad {$rowNum}: Kunde inte skapa försändelse";
        }
    }

    return [
        'success' => true,
        'created' => $created,
        'updated' => 0,
        'errors' => $errors
    ];
}

/**
 * Hitta kolumnindex baserat på möjliga namn
 */
function findColumn(array $headers, array $possibleNames): int|false
{
    foreach ($possibleNames as $name) {
        $index = array_search($name, $headers);
        if ($index !== false) {
            return $index;
        }
        // Försök case-insensitive
        foreach ($headers as $i => $header) {
            if (mb_strtolower($header) === mb_strtolower($name)) {
                return $i;
            }
        }
    }
    return false;
}

if ($type === 'templates') {
    $pageTitle = t('partner.templates.import.title');
    $backUrl = 'templates.php';
} elseif ($type === 'shipments') {
    $pageTitle = t('partner.shipments.import.title');
    $backUrl = 'shipments.php?tab=' . $tab;
} else {
    $pageTitle = t('partner.import.title');
    $backUrl = 'articles.php';
}
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Partner Portal</title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <link rel="stylesheet" href="css/partner.css">
    <link rel="stylesheet" href="../assets/css/modal.css">
    <script src="../assets/js/modal.js"></script>
    <script src="js/sidebar.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="header">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <a href="<?= $backUrl ?>" class="btn"><?= t('common.back') ?></a>
        </div>

        <div class="content">
            <?php if ($result): ?>
            <div class="card">
                <?php if ($result['success']): ?>
                <div class="alert alert-success">
                    <?= t('partner.import.success', ['created' => $result['created'], 'updated' => $result['updated']]) ?>
                </div>
                <?php if (!empty($result['errors'])): ?>
                <div class="alert alert-warning">
                    <strong><?= t('common.warnings') ?>:</strong>
                    <ul>
                        <?php foreach ($result['errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($result['error']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <h2><?= t('partner.import.upload_title') ?></h2>

                <form method="POST" enctype="multipart/form-data">
                    <?= Session::csrfField() ?>

                    <div class="form-group">
                        <label for="csv_file"><?= t('partner.import.select_file') ?></label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <small class="form-hint"><?= t('partner.import.file_hint') ?></small>
                    </div>

                    <button type="submit" class="btn btn-primary"><?= t('partner.import.button') ?></button>
                </form>
            </div>

            <div class="card">
                <h2><?= t('partner.import.expected_columns') ?></h2>
                <p><?= t('partner.import.columns_intro') ?></p>
                <?php if ($type === 'articles'): ?>
                <ul>
                    <li><strong>SKU</strong> (<?= t('partner.import.sku_hint') ?>)</li>
                    <?php foreach ($articleSchema as $field): ?>
                    <li><?= htmlspecialchars($field['label']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="text-muted"><?= t('partner.import.update_hint') ?></p>
                <?php elseif ($type === 'shipments'): ?>
                <ul>
                    <?php if ($tab === 'outgoing'): ?>
                    <li><strong><?= t('partner.shipments.form.customer') ?></strong> (<?= t('partner.shipments.import.partner_hint') ?>)</li>
                    <li><strong>to_org_id</strong> (<?= t('partner.shipments.import.partner_id_hint') ?>)</li>
                    <?php else: ?>
                    <li><strong><?= t('partner.shipments.form.supplier') ?></strong> (<?= t('partner.shipments.import.partner_hint') ?>)</li>
                    <li><strong>from_org_id</strong> (<?= t('partner.shipments.import.partner_id_hint') ?>)</li>
                    <?php endif; ?>
                    <li><?= t('partner.shipments.form.sales_order_id') ?></li>
                    <li><?= t('partner.shipments.form.purchase_order_id') ?></li>
                    <li><?= $tab === 'outgoing' ? t('partner.shipments.form.from_unit') : t('partner.shipments.form.to_unit') ?></li>
                    <li><?= $tab === 'outgoing' ? 'from_unit_id' : 'to_unit_id' ?> (<?= t('partner.shipments.import.unit_id_hint') ?>)</li>
                    <li><?= t('partner.shipments.form.notes') ?></li>
                </ul>
                <?php if (!empty($relatedOrgs)): ?>
                <p class="text-muted"><?= t('partner.shipments.import.partners_available') ?>:</p>
                <ul class="text-muted">
                    <?php foreach ($relatedOrgs as $id => $name): ?>
                        <?php if (is_string($name)): ?>
                        <li><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($id) ?>)</li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <?php if (!empty($units)): ?>
                <p class="text-muted"><?= t('partner.shipments.import.units_available') ?>:</p>
                <ul class="text-muted">
                    <?php foreach ($units as $id => $name): ?>
                        <?php if (is_string($name)): ?>
                        <li><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($id) ?>)</li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <?php elseif ($type === 'templates'): ?>
                <ul>
                    <li><strong><?= t('partner.templates.form.label') ?></strong> (<?= t('partner.templates.import.label_hint') ?>)</li>
                    <li><strong><?= t('partner.templates.form.event_type') ?></strong> (<?= t('partner.templates.import.event_type_hint') ?>)</li>
                    <li><?= t('partner.templates.form.unit') ?> (<?= t('partner.templates.import.unit_hint') ?>)</li>
                    <li><?= t('partner.templates.form.target_unit') ?> (<?= t('partner.templates.import.target_unit_hint') ?>)</li>
                    <li><?= t('partner.templates.form.is_reusable') ?> (<?= t('partner.templates.import.reusable_hint') ?>)</li>
                    <li><?= t('partner.templates.form.notes') ?></li>
                </ul>
                <?php if (!empty($eventTypes)): ?>
                <p class="text-muted"><?= t('partner.templates.import.event_types_available') ?>:</p>
                <ul class="text-muted">
                    <?php foreach ($eventTypes as $key => $et): ?>
                        <?php if (is_array($et)): ?>
                        <li><?= htmlspecialchars($et['code']) ?> - <?= htmlspecialchars($et['name_sv']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <?php if (!empty($units)): ?>
                <p class="text-muted"><?= t('partner.templates.import.units_available') ?>:</p>
                <ul class="text-muted">
                    <?php foreach ($units as $id => $name): ?>
                        <?php if (is_string($name)): ?>
                        <li><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($id) ?>)</li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
