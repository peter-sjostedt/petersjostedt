<?php
/**
 * Partner Portal - Organisationsrelationer
 * Hantera kunder och leverantörer
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
$message = '';
$messageType = '';

// Aktuell flik (customers = kunder, suppliers = leverantörer)
$tab = $_GET['tab'] ?? 'customers';
if (!in_array($tab, ['customers', 'suppliers'])) {
    $tab = 'customers';
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

/**
 * Bearbeta relations CSV-import
 */
function processRelationImport(string $filePath, string $orgId, string $relationType, PDO $db): array
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

    // Hitta kolumnindex
    $orgIdCol = findColumn($headers, ['org_id', 'partner_org_id', 'Organization ID', 'Organisations-ID']);
    $nameCol = findColumn($headers, ['name', 'Name', 'Namn', 'Organization', 'Organisation']);
    $statusCol = findColumn($headers, ['Status', 'status', 'is_active']);

    if ($orgIdCol === false && $nameCol === false) {
        return ['success' => false, 'error' => t('partner.import.error.missing_column', ['column' => 'org_id eller name'])];
    }

    // Bygg lookup för organisationer baserat på namn
    $stmt = $db->prepare("SELECT id, name FROM organizations WHERE is_active = 1");
    $stmt->execute();
    $allOrgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $orgLookup = [];
    foreach ($allOrgs as $org) {
        $orgLookup[$org['id']] = $org['name'];
        $orgLookup[mb_strtolower($org['name'])] = $org['id'];
    }

    // Hämta befintliga relationer med ID för uppdatering
    $stmt = $db->prepare("SELECT id, partner_org_id, is_active FROM organization_relations WHERE organization_id = ? AND relation_type = ?");
    $stmt->execute([$orgId, $relationType]);
    $existingRelations = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rel) {
        $existingRelations[$rel['partner_org_id']] = $rel;
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

        // Hitta organisations-ID
        $partnerId = null;
        $orgIdValue = $orgIdCol !== false ? trim($values[$orgIdCol] ?? '') : '';
        $nameValue = $nameCol !== false ? trim($values[$nameCol] ?? '') : '';

        if (!empty($orgIdValue) && isset($orgLookup[$orgIdValue])) {
            $partnerId = $orgIdValue;
        } elseif (!empty($nameValue)) {
            if (isset($orgLookup[$nameValue])) {
                $partnerId = $nameValue;
            } elseif (isset($orgLookup[mb_strtolower($nameValue)])) {
                $partnerId = $orgLookup[mb_strtolower($nameValue)];
            }
        }

        if (!$partnerId) {
            $displayValue = !empty($orgIdValue) ? $orgIdValue : $nameValue;
            $errors[] = "Rad {$rowNum}: Okänd organisation '{$displayValue}'";
            continue;
        }

        // Kan inte lägga till sig själv
        if ($partnerId === $orgId) {
            $errors[] = "Rad {$rowNum}: Kan inte lägga till egen organisation";
            continue;
        }

        // Läs status från CSV (default aktiv)
        $isActive = 1;
        if ($statusCol !== false) {
            $statusValue = mb_strtolower(trim($values[$statusCol] ?? ''));
            // Kolla om det är inaktiv
            if (in_array($statusValue, ['inaktiv', 'inactive', '0', 'false', 'nej', 'no'])) {
                $isActive = 0;
            }
        }

        // Kolla om redan finns
        if (isset($existingRelations[$partnerId])) {
            // Uppdatera befintlig relation om status skiljer sig
            $existing = $existingRelations[$partnerId];
            if ((int)$existing['is_active'] !== $isActive) {
                $stmt = $db->prepare("UPDATE organization_relations SET is_active = ? WHERE id = ?");
                if ($stmt->execute([$isActive, $existing['id']])) {
                    $updated++;
                } else {
                    $errors[] = "Rad {$rowNum}: Kunde inte uppdatera relation";
                }
            }
            continue;
        }

        // Lägg till ny relation
        $stmt = $db->prepare("INSERT INTO organization_relations (organization_id, partner_org_id, relation_type, is_active) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$orgId, $partnerId, $relationType, $isActive])) {
            $created++;
            $existingRelations[$partnerId] = ['id' => $db->lastInsertId(), 'partner_org_id' => $partnerId, 'is_active' => $isActive];
        } else {
            $errors[] = "Rad {$rowNum}: Kunde inte skapa relation";
        }
    }

    return [
        'success' => true,
        'created' => $created,
        'updated' => $updated,
        'errors' => $errors
    ];
}

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add':
                $partnerOrgId = trim($_POST['partner_org_id'] ?? '');
                $relationType = $_POST['relation_type'] ?? $tab;

                // Mappa flik till relationstyp
                if ($relationType === 'customers') {
                    $relationType = 'customer';
                } elseif ($relationType === 'suppliers') {
                    $relationType = 'supplier';
                }

                if (empty($partnerOrgId) || empty($relationType)) {
                    $message = t('partner.relations.error.required_fields');
                    $messageType = 'error';
                } elseif ($partnerOrgId === $organizationId) {
                    $message = t('partner.relations.error.cannot_add_self');
                    $messageType = 'error';
                } else {
                    // Kontrollera att organisationen finns
                    $stmt = $db->prepare("SELECT id, name FROM organizations WHERE id = ?");
                    $stmt->execute([$partnerOrgId]);
                    $partnerOrg = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$partnerOrg) {
                        $message = t('partner.relations.error.org_not_found');
                        $messageType = 'error';
                    } else {
                        // Kontrollera om relationen redan finns
                        $stmt = $db->prepare("SELECT id FROM organization_relations WHERE organization_id = ? AND partner_org_id = ? AND relation_type = ?");
                        $stmt->execute([$organizationId, $partnerOrgId, $relationType]);
                        if ($stmt->fetch()) {
                            $message = t('partner.relations.error.already_exists');
                            $messageType = 'error';
                        } else {
                            $stmt = $db->prepare("INSERT INTO organization_relations (organization_id, partner_org_id, relation_type) VALUES (?, ?, ?)");
                            if ($stmt->execute([$organizationId, $partnerOrgId, $relationType])) {
                                $message = t('partner.relations.message.added', ['name' => $partnerOrg['name']]);
                                $messageType = 'success';
                                Logger::getInstance()->info('RELATION_ADD', $userId, "Lade till relation: {$partnerOrgId} ({$relationType})");
                            } else {
                                $message = t('error.generic');
                                $messageType = 'error';
                            }
                        }
                    }
                }
                break;

            case 'delete':
                $relationId = (int)($_POST['relation_id'] ?? 0);

                $stmt = $db->prepare("DELETE FROM organization_relations WHERE id = ? AND organization_id = ?");
                if ($stmt->execute([$relationId, $organizationId]) && $stmt->rowCount() > 0) {
                    $message = t('partner.relations.message.deleted');
                    $messageType = 'success';
                    Logger::getInstance()->info('RELATION_DELETE', $userId, "Tog bort relation ID: {$relationId}");
                } else {
                    $message = t('error.generic');
                    $messageType = 'error';
                }
                break;

            case 'toggle':
                $relationId = (int)($_POST['relation_id'] ?? 0);

                $stmt = $db->prepare("UPDATE organization_relations SET is_active = NOT is_active WHERE id = ? AND organization_id = ?");
                if ($stmt->execute([$relationId, $organizationId]) && $stmt->rowCount() > 0) {
                    $message = t('partner.relations.message.updated');
                    $messageType = 'success';
                } else {
                    $message = t('error.generic');
                    $messageType = 'error';
                }
                break;

            case 'import':
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    Session::flash('error', t('partner.import.error.no_file'));
                    header('Location: relations.php?tab=' . $tab);
                    exit;
                }

                $relationType = $tab === 'customers' ? 'customer' : 'supplier';
                $result = processRelationImport(
                    $_FILES['csv_file']['tmp_name'],
                    $organizationId,
                    $relationType,
                    $db
                );

                if ($result['success']) {
                    $msg = t('partner.import.success', ['created' => $result['created'], 'updated' => $result['updated']]);
                    if (!empty($result['errors'])) {
                        $msg .= ' ' . t('common.warnings') . ': ' . count($result['errors']);
                    }
                    Logger::getInstance()->info('RELATION_IMPORT', $userId, "Importerade {$result['created']} nya, {$result['updated']} uppdaterade relationer ({$relationType})");
                    Session::flash('success', $msg);
                } else {
                    Session::flash('error', $result['error']);
                }
                header('Location: relations.php?tab=' . $tab);
                exit;
        }
    }
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

// Hämta befintliga relationer
$stmt = $db->prepare("
    SELECT r.id, r.partner_org_id, r.relation_type, r.is_active, r.created_at,
           o.name as partner_name
    FROM organization_relations r
    JOIN organizations o ON r.partner_org_id = o.id
    WHERE r.organization_id = ?
    ORDER BY r.relation_type, o.name
");
$stmt->execute([$organizationId]);
$relations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separera kunder och leverantörer
$customers = array_filter($relations, fn($r) => $r['relation_type'] === 'customer');
$suppliers = array_filter($relations, fn($r) => $r['relation_type'] === 'supplier');

// Antal per flik
$countCustomers = count($customers);
$countSuppliers = count($suppliers);

// Aktuell lista baserat på flik
$currentRelations = $tab === 'customers' ? $customers : $suppliers;

// Hämta alla organisationer för dropdown (exkludera egen och redan tillagda för denna typ)
$currentTypeIds = array_column($currentRelations, 'partner_org_id');
$excludeIds = array_merge([$organizationId], $currentTypeIds);
$placeholders = implode(',', array_fill(0, count($excludeIds), '?'));

$stmt = $db->prepare("SELECT id, name FROM organizations WHERE id NOT IN ({$placeholders}) AND is_active = 1 ORDER BY name");
$stmt->execute($excludeIds);
$availableOrganizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lang = Language::getInstance()->getLanguage();
$pageTitle = t('partner.relations.title') . ' - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="relations-labels" content='<?= json_encode([
        'organization' => t('partner.relations.form.organization'),
        'select_org' => t('partner.relations.form.select_org'),
        'type_customer' => t('partner.relations.type.customer'),
        'type_supplier' => t('partner.relations.type.supplier'),
        'create' => t('common.add'),
        'cancel' => t('common.cancel'),
        'delete' => t('common.delete'),
        'modal_create_customer' => t('partner.relations.modal.create_customer'),
        'modal_create_supplier' => t('partner.relations.modal.create_supplier'),
        'modal_delete' => t('partner.relations.modal.delete'),
        'confirm_delete' => t('partner.relations.confirm.delete'),
        'modal_import' => t('partner.relations.modal.import'),
        'import' => t('partner.import.button'),
        'import_select_file' => t('partner.import.select_file'),
        'import_file_hint' => t('partner.import.file_hint'),
        'import_columns' => t('partner.import.expected_columns'),
        'import_org_id_hint' => t('partner.relations.import.org_id_hint'),
        'import_name_hint' => t('partner.relations.import.name_hint'),
        'import_status_hint' => t('partner.relations.import.status_hint')
    ]) ?>'>
    <meta name="relations-data" content='<?= htmlspecialchars(json_encode([
        'organizations' => $availableOrganizations,
        'tab' => $tab
    ]), ENT_QUOTES) ?>'>
    <link rel="stylesheet" href="css/partner.css?v=<?= filemtime(__DIR__ . '/css/partner.css') ?>">
    <link rel="stylesheet" href="../assets/css/modal.css?v=<?= filemtime(__DIR__ . '/../assets/css/modal.css') ?>">
    <script src="../assets/js/modal.js?v=<?= filemtime(__DIR__ . '/../assets/js/modal.js') ?>"></script>
    <script src="js/sidebar.js?v=<?= filemtime(__DIR__ . '/js/sidebar.js') ?>" defer></script>
    <script src="js/relations.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h1><?= t('partner.relations.heading') ?></h1>
            <div class="page-actions">
                <div class="search-box">
                    <input type="text" id="table-search" placeholder="<?= t('common.search') ?>...">
                    <button type="button" class="search-clear" title="<?= t('common.cancel') ?>">&times;</button>
                </div>
                <button type="button" class="btn" id="importRelationBtn"><?= t('partner.import.button') ?></button>
                <a href="export.php?type=relations&tab=<?= $tab ?>" class="btn"><?= t('partner.export.button') ?></a>
                <button type="button" class="btn btn-primary" id="createRelationBtn">
                    <?= $tab === 'customers' ? t('partner.relations.action.add_customer') : t('partner.relations.action.add_supplier') ?>
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Flikar -->
        <div class="tabs">
            <a href="?tab=customers" class="tab <?= $tab === 'customers' ? 'active' : '' ?>">
                👥 <?= t('partner.relations.tab.customers') ?>
                <span class="badge"><?= $countCustomers ?></span>
            </a>
            <a href="?tab=suppliers" class="tab <?= $tab === 'suppliers' ? 'active' : '' ?>">
                🏭 <?= t('partner.relations.tab.suppliers') ?>
                <span class="badge"><?= $countSuppliers ?></span>
            </a>
        </div>
        <div class="card">
            <table id="relations-table">
                <thead>
                    <tr>
                        <th><?= t('partner.relations.table.name') ?></th>
                        <th><?= t('partner.relations.table.org_id') ?></th>
                        <th><?= t('partner.relations.table.status') ?></th>
                        <th><?= t('partner.relations.table.added') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($currentRelations)): ?>
                    <tr>
                        <td colspan="5" class="text-muted text-center">
                            <?= $tab === 'customers' ? t('partner.relations.customers.empty') : t('partner.relations.suppliers.empty') ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($currentRelations as $rel): ?>
                    <tr class="<?= !$rel['is_active'] ? 'row-inactive' : '' ?>">
                        <td><strong><?= htmlspecialchars($rel['partner_name']) ?></strong></td>
                        <td><?= htmlspecialchars($rel['partner_org_id']) ?></td>
                        <td>
                            <?php if ($rel['is_active']): ?>
                                <span class="badge badge-new"><?= t('partner.relations.status.active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-inactive"><?= t('partner.relations.status.inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('Y-m-d', strtotime($rel['created_at'])) ?></td>
                        <td class="actions">
                            <form method="POST" style="display:inline;">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="relation_id" value="<?= $rel['id'] ?>">
                                <button type="submit" class="btn btn-icon" title="<?= $rel['is_active'] ? t('partner.relations.action.deactivate') : t('partner.relations.action.activate') ?>">
                                    <?= $rel['is_active'] ? '⏸️' : '▶️' ?>
                                </button>
                            </form>
                            <button type="button" class="btn btn-icon btn-icon-danger" data-relation-delete="<?= $rel['id'] ?>" data-label="<?= htmlspecialchars($rel['partner_name']) ?>" title="<?= t('common.delete') ?>">🗑️</button>
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
