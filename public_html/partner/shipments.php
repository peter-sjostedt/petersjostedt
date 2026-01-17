<?php
/**
 * Partner Portal - Försändelser
 * Hantera utgående och inkommande försändelser med flikar
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

// Hantera språkbyte
if (isset($_GET['set_lang'])) {
    Language::getInstance()->setLanguage($_GET['set_lang']);
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['set_lang']);
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

// Kräv inloggning
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// System admin redirectas till admin
if (Session::isSystemAdmin()) {
    header('Location: ../admin/index.php');
    exit;
}

// Kräv org_admin roll
if (!Session::isOrgAdmin()) {
    Session::flash('error', t('error.unauthorized'));
    header('Location: login.php');
    exit;
}

$userData = Session::getUserData();
$organizationId = Session::getOrganizationId();
$userId = Session::getUserId();

// Hämta organisationsdata
$orgModel = new Organization();
$organization = $orgModel->findById($organizationId);

if (!$organization) {
    Session::flash('error', t('error.unauthorized'));
    Session::logout();
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getPdo();
$shipmentModel = new Shipment();
$message = '';
$messageType = '';

// Aktuell flik (outgoing = utgående, incoming = inkommande)
$tab = $_GET['tab'] ?? 'outgoing';
if (!in_array($tab, ['outgoing', 'incoming'])) {
    $tab = 'outgoing';
}

// Hämta kunder (för utgående försändelser)
$stmt = $db->prepare("
    SELECT o.id, o.name
    FROM organizations o
    INNER JOIN organization_relations r ON r.partner_org_id = o.id
    WHERE r.organization_id = ? AND r.relation_type = 'customer' AND r.is_active = 1
    ORDER BY o.name
");
$stmt->execute([$organizationId]);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hämta leverantörer (för inkommande försändelser)
$stmt = $db->prepare("
    SELECT o.id, o.name
    FROM organizations o
    INNER JOIN organization_relations r ON r.partner_org_id = o.id
    WHERE r.organization_id = ? AND r.relation_type = 'supplier' AND r.is_active = 1
    ORDER BY o.name
");
$stmt->execute([$organizationId]);
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hämta enheter för dropdown
$stmt = $db->prepare("SELECT id, name FROM units WHERE organization_id = ? AND is_active = 1 ORDER BY name");
$stmt->execute([$organizationId]);
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_outgoing':
                $toOrgId = trim($_POST['to_org_id'] ?? '');
                $salesOrderId = trim($_POST['sales_order_id'] ?? '');
                $purchaseOrderId = trim($_POST['purchase_order_id'] ?? '');
                $fromUnitId = !empty($_POST['from_unit_id']) ? (int)$_POST['from_unit_id'] : null;
                $notes = trim($_POST['notes'] ?? '');

                // Validering
                $errors = [];
                if (empty($toOrgId)) {
                    $errors[] = t('partner.shipments.error.customer_required');
                }

                if (empty($errors)) {
                    $shipmentId = $shipmentModel->create($organizationId, $toOrgId, [
                        'sales_order_id' => $salesOrderId ?: null,
                        'purchase_order_id' => $purchaseOrderId ?: null,
                        'from_unit_id' => $fromUnitId,
                        'notes' => $notes ?: null,
                        'created_by_user_id' => $userId
                    ]);

                    if ($shipmentId) {
                        $shipment = $shipmentModel->findById($shipmentId);
                        Logger::getInstance()->info('SHIPMENT_CREATE', $userId, "Skapade försändelse: {$shipment['qr_code']}");
                        Session::flash('success', t('partner.shipments.message.created', ['id' => $shipment['qr_code']]));
                        header('Location: shipments.php?tab=outgoing');
                        exit;
                    } else {
                        $message = t('error.generic');
                        $messageType = 'error';
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;

            case 'create_incoming':
                $fromOrgId = trim($_POST['from_org_id'] ?? '');
                $purchaseOrderId = trim($_POST['purchase_order_id'] ?? '');
                $salesOrderId = trim($_POST['sales_order_id'] ?? '');
                $toUnitId = !empty($_POST['to_unit_id']) ? (int)$_POST['to_unit_id'] : null;
                $notes = trim($_POST['notes'] ?? '');

                // Validering
                $errors = [];
                if (empty($fromOrgId)) {
                    $errors[] = t('partner.shipments.error.supplier_required');
                }

                if (empty($errors)) {
                    $shipmentId = $shipmentModel->create($fromOrgId, $organizationId, [
                        'sales_order_id' => $salesOrderId ?: null,
                        'purchase_order_id' => $purchaseOrderId ?: null,
                        'to_unit_id' => $toUnitId,
                        'notes' => $notes ?: null,
                        'created_by_user_id' => $userId
                    ]);

                    if ($shipmentId) {
                        $shipment = $shipmentModel->findById($shipmentId);
                        Logger::getInstance()->info('SHIPMENT_CREATE', $userId, "Skapade inleverans: {$shipment['qr_code']}");
                        Session::flash('success', t('partner.shipments.message.created', ['id' => $shipment['qr_code']]));
                        header('Location: shipments.php?tab=incoming');
                        exit;
                    } else {
                        $message = t('error.generic');
                        $messageType = 'error';
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;

            case 'update':
                $shipmentIdNum = (int)($_POST['shipment_id'] ?? 0);
                $salesOrderId = trim($_POST['sales_order_id'] ?? '');
                $purchaseOrderId = trim($_POST['purchase_order_id'] ?? '');
                $notes = trim($_POST['notes'] ?? '');

                // Verifiera ägarskap
                $existing = $shipmentModel->findByIdAndOrganization($shipmentIdNum, $organizationId);
                if (!$existing) {
                    Session::flash('error', t('error.not_found'));
                    header('Location: shipments.php?tab=' . $tab);
                    exit;
                }

                // Kan endast uppdatera om status är 'prepared'
                if ($existing['status'] !== 'prepared') {
                    Session::flash('error', t('partner.shipments.error.cannot_update_shipped'));
                    header('Location: shipments.php?tab=' . $tab);
                    exit;
                }

                if ($shipmentModel->update($shipmentIdNum, [
                    'sales_order_id' => $salesOrderId ?: null,
                    'purchase_order_id' => $purchaseOrderId ?: null,
                    'notes' => $notes ?: null
                ])) {
                    Logger::getInstance()->info('SHIPMENT_UPDATE', $userId, "Uppdaterade försändelse: {$existing['qr_code']}");
                    Session::flash('success', t('partner.shipments.message.updated'));
                    header('Location: shipments.php?tab=' . $tab);
                    exit;
                } else {
                    $message = t('error.generic');
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $shipmentIdNum = (int)($_POST['shipment_id'] ?? 0);

                // Verifiera ägarskap
                $existing = $shipmentModel->findByIdAndOrganization($shipmentIdNum, $organizationId);
                if (!$existing) {
                    Session::flash('error', t('error.not_found'));
                    header('Location: shipments.php?tab=' . $tab);
                    exit;
                }

                if ($shipmentModel->delete($shipmentIdNum)) {
                    Logger::getInstance()->info('SHIPMENT_DELETE', $userId, "Raderade försändelse: {$existing['qr_code']}");
                    Session::flash('success', t('partner.shipments.message.deleted'));
                } else {
                    Session::flash('error', t('partner.shipments.error.cannot_delete'));
                }
                header('Location: shipments.php?tab=' . $tab);
                exit;

            case 'cancel':
                $shipmentIdNum = (int)($_POST['shipment_id'] ?? 0);

                // Verifiera ägarskap
                $existing = $shipmentModel->findByIdAndOrganization($shipmentIdNum, $organizationId);
                if (!$existing) {
                    Session::flash('error', t('error.not_found'));
                    header('Location: shipments.php?tab=' . $tab);
                    exit;
                }

                if ($shipmentModel->cancel($shipmentIdNum)) {
                    Logger::getInstance()->info('SHIPMENT_CANCEL', $userId, "Avbröt försändelse: {$existing['qr_code']}");
                    Session::flash('success', t('partner.shipments.message.cancelled'));
                } else {
                    Session::flash('error', t('error.generic'));
                }
                header('Location: shipments.php?tab=' . $tab);
                exit;

            case 'import':
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    Session::flash('error', t('partner.import.error.no_file'));
                    header('Location: shipments.php?tab=' . $tab);
                    exit;
                }

                $result = processShipmentImport(
                    $_FILES['csv_file']['tmp_name'],
                    $organizationId,
                    $tab === 'outgoing' ? $customers : $suppliers,
                    $units,
                    $userId,
                    $tab,
                    $db
                );

                if ($result['success']) {
                    $msg = t('partner.import.success', ['created' => $result['created'], 'updated' => $result['updated']]);
                    if (!empty($result['errors'])) {
                        $msg .= ' ' . t('common.warnings') . ': ' . count($result['errors']);
                    }
                    Logger::getInstance()->info('SHIPMENT_IMPORT', $userId, "Importerade {$result['created']} försändelser");
                    Session::flash('success', $msg);
                } else {
                    Session::flash('error', $result['error']);
                }
                header('Location: shipments.php?tab=' . $tab);
                exit;
        }
    }
}

/**
 * Bearbeta shipment CSV-import (inlined från import.php)
 */
function processShipmentImport(string $filePath, string $orgId, array $partners, array $unitsList, int $userId, string $tab, PDO $db): array
{
    $content = file_get_contents($filePath);
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    $firstLine = strtok($content, "\n");
    $colsWithComma = count(str_getcsv($firstLine, ','));
    $colsWithSemicolon = count(str_getcsv($firstLine, ';'));
    $separator = ($colsWithSemicolon > $colsWithComma) ? ';' : ',';

    $lines = array_filter(explode("\n", $content), 'trim');
    if (count($lines) < 2) {
        return ['success' => false, 'error' => t('partner.import.error.empty_file')];
    }

    $headers = str_getcsv(array_shift($lines), $separator);
    $headers = array_map('trim', $headers);

    // Bygg lookup-map för partners
    $relatedOrgs = [];
    foreach ($partners as $p) {
        $relatedOrgs[$p['id']] = $p['name'];
        $relatedOrgs[mb_strtolower($p['name'])] = $p['id'];
    }

    // Bygg lookup-map för enheter
    $units = [];
    foreach ($unitsList as $u) {
        $units[$u['id']] = $u['name'];
        $units[mb_strtolower($u['name'])] = $u['id'];
    }

    // Hitta kolumnindex
    $partnerCol = $tab === 'outgoing'
        ? findColumn($headers, ['Mottagare', 'Kund', 'Customer', 'Recipient', 'to_org'])
        : findColumn($headers, ['Leverantör', 'Supplier', 'from_org']);
    $partnerIdCol = $tab === 'outgoing'
        ? findColumn($headers, ['to_org_id'])
        : findColumn($headers, ['from_org_id']);
    $salesOrderCol = findColumn($headers, ['Försäljningsorder', 'Sales Order', 'SO', 'sales_order_id']);
    $purchaseOrderCol = findColumn($headers, ['Inköpsorder', 'Purchase Order', 'PO', 'purchase_order_id']);
    $unitCol = $tab === 'outgoing'
        ? findColumn($headers, ['Från enhet', 'From Unit', 'from_unit'])
        : findColumn($headers, ['Till enhet', 'To Unit', 'to_unit']);
    $unitIdCol = $tab === 'outgoing'
        ? findColumn($headers, ['from_unit_id'])
        : findColumn($headers, ['to_unit_id']);
    $notesCol = findColumn($headers, ['Anteckningar', 'Notes', 'notes']);

    if ($partnerCol === false && $partnerIdCol === false) {
        $colName = $tab === 'outgoing' ? 'Mottagare/Kund eller to_org_id' : 'Leverantör eller from_org_id';
        return ['success' => false, 'error' => t('partner.import.error.missing_column', ['column' => $colName])];
    }

    // Validera att rätt filtyp importeras genom att kolla org-ID i första dataraden
    // Utgående: from_org_id = egen org, Inkommande: to_org_id = egen org
    $fromOrgIdCol = findColumn($headers, ['from_org_id']);
    $toOrgIdCol = findColumn($headers, ['to_org_id']);

    if ($fromOrgIdCol !== false && $toOrgIdCol !== false && !empty($lines)) {
        $firstDataLine = trim($lines[0]);
        if (!empty($firstDataLine)) {
            $firstValues = str_getcsv($firstDataLine, $separator);
            $firstFromOrgId = trim($firstValues[$fromOrgIdCol] ?? '');
            $firstToOrgId = trim($firstValues[$toOrgIdCol] ?? '');

            // Om vi är på utgående-fliken ska from_org_id vara vår org
            if ($tab === 'outgoing' && $firstFromOrgId !== $orgId && $firstToOrgId === $orgId) {
                return ['success' => false, 'error' => t('partner.import.error.wrong_file_type_outgoing')];
            }
            // Om vi är på inkommande-fliken ska to_org_id vara vår org
            if ($tab === 'incoming' && $firstToOrgId !== $orgId && $firstFromOrgId === $orgId) {
                return ['success' => false, 'error' => t('partner.import.error.wrong_file_type_incoming')];
            }
        }
    }

    $shipmentModel = new Shipment();
    $created = 0;
    $updated = 0;
    $errors = [];
    $rowNum = 1;

    foreach ($lines as $line) {
        $rowNum++;
        $line = trim($line);
        if (empty($line)) continue;

        $values = str_getcsv($line, $separator);

        $partnerIdValue = $partnerIdCol !== false ? trim($values[$partnerIdCol] ?? '') : '';
        $partnerValue = $partnerCol !== false ? trim($values[$partnerCol] ?? '') : '';
        $salesOrderId = $salesOrderCol !== false ? trim($values[$salesOrderCol] ?? '') : '';
        $purchaseOrderId = $purchaseOrderCol !== false ? trim($values[$purchaseOrderCol] ?? '') : '';
        $unitIdValue = $unitIdCol !== false ? trim($values[$unitIdCol] ?? '') : '';
        $unitValue = $unitCol !== false ? trim($values[$unitCol] ?? '') : '';
        $notes = $notesCol !== false ? trim($values[$notesCol] ?? '') : '';

        // Hitta partner-ID
        $partnerId = null;
        if (!empty($partnerIdValue) && isset($relatedOrgs[$partnerIdValue])) {
            $partnerId = $partnerIdValue;
        } elseif (!empty($partnerValue)) {
            if (isset($relatedOrgs[$partnerValue])) {
                $partnerId = $partnerValue;
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

        // Hitta enhets-ID
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

        // Kolla om försändelse redan finns (baserat på partner + SO + PO)
        $fromOrgId = $tab === 'outgoing' ? $orgId : $partnerId;
        $toOrgId = $tab === 'outgoing' ? $partnerId : $orgId;

        $existing = $shipmentModel->findByOrderIds($fromOrgId, $toOrgId, $salesOrderId ?: null, $purchaseOrderId ?: null);

        if ($existing) {
            // Uppdatera befintlig försändelse (endast om status är 'prepared')
            if ($existing['status'] === 'prepared') {
                $updateData = ['notes' => $notes ?: null];
                if ($tab === 'outgoing' && $unitId) {
                    $updateData['from_unit_id'] = $unitId;
                } elseif ($tab === 'incoming' && $unitId) {
                    $updateData['to_unit_id'] = $unitId;
                }
                if ($shipmentModel->update($existing['id'], $updateData)) {
                    $updated++;
                } else {
                    $errors[] = "Rad {$rowNum}: Kunde inte uppdatera befintlig försändelse";
                }
            } else {
                $errors[] = "Rad {$rowNum}: Försändelse finns redan och kan inte uppdateras (status: {$existing['status']})";
            }
        } else {
            // Skapa ny försändelse
            if ($tab === 'outgoing') {
                $newId = $shipmentModel->create($orgId, $partnerId, [
                    'sales_order_id' => $salesOrderId ?: null,
                    'purchase_order_id' => $purchaseOrderId ?: null,
                    'from_unit_id' => $unitId,
                    'notes' => $notes ?: null,
                    'created_by_user_id' => $userId
                ]);
            } else {
                $newId = $shipmentModel->create($partnerId, $orgId, [
                    'sales_order_id' => $salesOrderId ?: null,
                    'purchase_order_id' => $purchaseOrderId ?: null,
                    'to_unit_id' => $unitId,
                    'notes' => $notes ?: null,
                    'created_by_user_id' => $userId
                ]);
            }

            if ($newId) {
                $created++;
            } else {
                $errors[] = "Rad {$rowNum}: Kunde inte skapa försändelse";
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
 * Hitta kolumnindex baserat på möjliga namn
 */
function findColumn(array $headers, array $possibleNames): int|false
{
    foreach ($possibleNames as $name) {
        $index = array_search($name, $headers);
        if ($index !== false) {
            return $index;
        }
        foreach ($headers as $i => $header) {
            if (mb_strtolower($header) === mb_strtolower($name)) {
                return $i;
            }
        }
    }
    return false;
}

// Visa flash-meddelande om det finns
if ($flash = Session::getFlash('success')) {
    $message = $flash;
    $messageType = 'success';
}
if ($flash = Session::getFlash('error')) {
    $message = $flash;
    $messageType = 'error';
}

// Hämta försändelser baserat på flik
if ($tab === 'outgoing') {
    $shipments = $shipmentModel->findOutgoing($organizationId);
    $countOutgoing = count($shipments);
    $countIncoming = $shipmentModel->countIncoming($organizationId);
} else {
    $shipments = $shipmentModel->findIncoming($organizationId);
    $countOutgoing = $shipmentModel->countOutgoing($organizationId);
    $countIncoming = count($shipments);
}

// Generera nästa QR-kod
$suggestedQrCode = $shipmentModel->generateQrCode();

$lang = Language::getInstance()->getLanguage();
$pageTitle = t('partner.shipments.title') . ' - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="shipments-labels" content='<?= json_encode([
        'qr_code' => t('partner.shipments.form.qr_code'),
        'customer' => t('partner.shipments.form.customer'),
        'select_customer' => t('partner.shipments.form.select_customer'),
        'no_customers' => t('partner.shipments.form.no_customers'),
        'supplier' => t('partner.shipments.form.supplier'),
        'select_supplier' => t('partner.shipments.form.select_supplier'),
        'no_suppliers' => t('partner.shipments.form.no_suppliers'),
        'sales_order_id' => t('partner.shipments.form.sales_order_id'),
        'sales_order_id_help' => t('partner.shipments.form.sales_order_id_help'),
        'purchase_order_id' => t('partner.shipments.form.purchase_order_id'),
        'purchase_order_id_help' => t('partner.shipments.form.purchase_order_id_help'),
        'from_unit' => t('partner.shipments.form.from_unit'),
        'to_unit' => t('partner.shipments.form.to_unit'),
        'select_unit' => t('partner.shipments.form.select_unit'),
        'notes' => t('partner.shipments.form.notes'),
        'create' => t('common.create'),
        'update' => t('common.update'),
        'delete' => t('common.delete'),
        'cancel' => t('common.cancel'),
        'modal_create_outgoing' => t('partner.shipments.modal.create_outgoing'),
        'modal_create_incoming' => t('partner.shipments.modal.create_incoming'),
        'modal_edit' => t('partner.shipments.modal.edit'),
        'modal_delete' => t('partner.shipments.modal.delete'),
        'confirm_delete' => t('partner.shipments.modal.confirm_delete'),
        'modal_import' => t('partner.shipments.modal.import'),
        'import' => t('partner.shipments.action.import'),
        'import_select_file' => t('partner.import.select_file'),
        'import_file_hint' => t('partner.import.file_hint'),
        'import_columns' => t('partner.import.expected_columns'),
        'import_partner_hint' => t('partner.shipments.import.partner_hint')
    ]) ?>'>
    <meta name="shipments-data" content='<?= htmlspecialchars(json_encode([
        'suggestedQrCode' => $suggestedQrCode,
        'customers' => $customers,
        'suppliers' => $suppliers,
        'units' => $units,
        'currentOrg' => [
            'id' => $organizationId,
            'name' => $organization['name']
        ],
        'tab' => $tab
    ]), ENT_QUOTES) ?>'>
    <link rel="stylesheet" href="css/partner.css?v=<?= filemtime(__DIR__ . '/css/partner.css') ?>">
    <link rel="stylesheet" href="../assets/css/modal.css?v=<?= filemtime(__DIR__ . '/../assets/css/modal.css') ?>">
    <script src="../assets/js/modal.js?v=<?= filemtime(__DIR__ . '/../assets/js/modal.js') ?>"></script>
    <script src="../assets/js/qr.js?v=<?= filemtime(__DIR__ . '/../assets/js/qr.js') ?>"></script>
    <script src="js/sidebar.js?v=<?= filemtime(__DIR__ . '/js/sidebar.js') ?>" defer></script>
    <script src="js/modals.js?v=<?= filemtime(__DIR__ . '/js/modals.js') ?>" defer></script>
    <script src="js/shipments.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h1><?= t('partner.shipments.heading') ?></h1>
            <div class="page-actions">
                <div class="search-box">
                    <input type="text" id="table-search" placeholder="<?= t('common.search') ?>...">
                    <button type="button" class="search-clear" title="<?= t('common.cancel') ?>">&times;</button>
                </div>
                <button type="button" class="btn" id="importShipmentBtn"><?= t('partner.shipments.action.import') ?></button>
                <a href="export.php?type=shipments&tab=<?= $tab ?>" class="btn"><?= t('partner.shipments.action.export') ?></a>
                <button type="button" class="btn btn-primary" id="createShipmentBtn">
                    <?= $tab === 'outgoing' ? t('partner.shipments.action.create_outgoing') : t('partner.shipments.action.create_incoming') ?>
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Flikar -->
        <div class="tabs">
            <a href="?tab=outgoing" class="tab <?= $tab === 'outgoing' ? 'active' : '' ?>">
                📤 <?= t('partner.shipments.tab.outgoing') ?>
                <span class="badge"><?= $countOutgoing ?></span>
            </a>
            <a href="?tab=incoming" class="tab <?= $tab === 'incoming' ? 'active' : '' ?>">
                📥 <?= t('partner.shipments.tab.incoming') ?>
                <span class="badge"><?= $countIncoming ?></span>
            </a>
        </div>
        <div class="card">
            <table id="shipments-table">
                <thead>
                    <tr>
                        <th><?= t('partner.shipments.table.qr_code') ?></th>
                        <th><?= $tab === 'outgoing' ? t('partner.shipments.table.customer') : t('partner.shipments.table.supplier') ?></th>
                        <th><?= t('partner.shipments.table.orders') ?></th>
                        <th><?= t('partner.shipments.table.status') ?></th>
                        <th><?= t('partner.shipments.table.created') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shipments)): ?>
                    <tr>
                        <td colspan="6" class="text-muted text-center">
                            <?= $tab === 'outgoing' ? t('partner.shipments.list.empty_outgoing') : t('partner.shipments.list.empty_incoming') ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($shipments as $shipment):
                        $partnerName = $tab === 'outgoing' ? $shipment['to_org_name'] : $shipment['from_org_name'];
                        $orders = [];
                        if (!empty($shipment['sales_order_id'])) {
                            $orders[] = 'SO: ' . $shipment['sales_order_id'];
                        }
                        if (!empty($shipment['purchase_order_id'])) {
                            $orders[] = 'PO: ' . $shipment['purchase_order_id'];
                        }
                        $ordersStr = !empty($orders) ? implode(', ', $orders) : '-';

                        $statusLabel = t('partner.shipments.status.' . $shipment['status']);
                        $statusClass = Shipment::getStatusBadgeClass($shipment['status']);

                        $shipmentData = htmlspecialchars(json_encode([
                            'id' => $shipment['id'],
                            'qr_code' => $shipment['qr_code'],
                            'from_org_id' => $shipment['from_org_id'],
                            'to_org_id' => $shipment['to_org_id'],
                            'from_unit_id' => $shipment['from_unit_id'],
                            'to_unit_id' => $shipment['to_unit_id'],
                            'sales_order_id' => $shipment['sales_order_id'],
                            'purchase_order_id' => $shipment['purchase_order_id'],
                            'status' => $shipment['status'],
                            'notes' => $shipment['notes']
                        ], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

                        // QR-data
                        $qrLines = [
                            t('partner.shipments.qr.from') . ': ' . ($tab === 'outgoing' ? $organization['name'] : $shipment['from_org_name']),
                            t('partner.shipments.qr.to') . ': ' . ($tab === 'outgoing' ? $shipment['to_org_name'] : $organization['name'])
                        ];
                        if (!empty($shipment['sales_order_id'])) {
                            $qrLines[] = 'SO: ' . $shipment['sales_order_id'];
                        }
                        if (!empty($shipment['purchase_order_id'])) {
                            $qrLines[] = 'PO: ' . $shipment['purchase_order_id'];
                        }
                        $qrConfig = [
                            'data' => [
                                'type' => 'shipment',
                                'qr_code' => $shipment['qr_code'],
                                'shipment_id' => $shipment['id'],
                                'from_org' => $shipment['from_org_id'],
                                'to_org' => $shipment['to_org_id'],
                                'sales_order' => $shipment['sales_order_id'],
                                'purchase_order' => $shipment['purchase_order_id']
                            ],
                            'title' => $shipment['qr_code'],
                            'subtitle' => implode("\n", $qrLines),
                            'filename' => 'Shipment_' . $shipment['qr_code']
                        ];
                        $qrData = htmlspecialchars(json_encode($qrConfig, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                        $canEdit = $shipment['status'] === 'prepared';
                        $canDelete = $shipment['status'] === 'prepared';
                    ?>
                    <tr class="<?= $shipment['status'] === 'cancelled' ? 'row-inactive' : '' ?>">
                        <td><strong><?= htmlspecialchars($shipment['qr_code']) ?></strong></td>
                        <td><?= htmlspecialchars($partnerName) ?></td>
                        <td><?= htmlspecialchars($ordersStr) ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                        <td><?= date('Y-m-d H:i', strtotime($shipment['created_at'])) ?></td>
                        <td class="actions">
                            <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-icon" data-shipment-edit="<?= $shipmentData ?>" title="<?= t('common.edit') ?>">✏️</button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-icon" data-qr="<?= $qrData ?>" title="<?= t('partner.shipments.action.qr') ?>">📱</button>
                            <?php if ($canDelete): ?>
                            <button type="button" class="btn btn-icon btn-icon-danger" data-shipment-delete="<?= $shipment['id'] ?>" data-label="<?= htmlspecialchars($shipment['qr_code']) ?>" title="<?= t('common.delete') ?>">🗑️</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal overlay -->
    <div id="modal-overlay" class="hidden">
        <div class="modal-container">
            <div id="modal-content"></div>
        </div>
    </div>
</body>
</html>
