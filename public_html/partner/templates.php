<?php
/**
 * Partner Portal - Händelsemallar (Event Templates)
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

// Hämta enheter för dropdown
$stmt = $db->prepare("SELECT id, name FROM units WHERE organization_id = ? AND is_active = 1 ORDER BY name");
$stmt->execute([$organizationId]);
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hämta händelsetyper
$eventTypeModel = new EventType();
$eventTypes = $eventTypeModel->getAll();

// Bygg lookup för enheter (för import)
$unitImportLookup = [];
foreach ($units as $u) {
    $unitImportLookup[$u['id']] = $u['name'];
    $unitImportLookup[mb_strtolower($u['name'])] = $u['id']; // Lookup by name
}

// Bygg lookup för händelsetyper (för import)
$eventTypeImportLookup = [];
foreach ($eventTypes as $et) {
    $eventTypeImportLookup[$et['id']] = $et;
    $eventTypeImportLookup[$et['code']] = $et['id']; // Lookup by code
    $eventTypeImportLookup[mb_strtolower($et['name_sv'])] = $et['id']; // Lookup by Swedish name
    $eventTypeImportLookup[mb_strtolower($et['name_en'])] = $et['id']; // Lookup by English name
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
        return ['success' => false, 'error' => t('partner.import.error.empty_file')];
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
        return ['success' => false, 'error' => t('partner.import.error.missing_column', ['column' => t('partner.templates.form.label')])];
    }
    if ($eventTypeCol === false) {
        return ['success' => false, 'error' => t('partner.import.error.missing_column', ['column' => t('partner.templates.form.event_type')])];
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
            $errors[] = "Rad {$rowNum}: " . t('partner.templates.error.label_required');
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
            $errors[] = "Rad {$rowNum}: " . t('partner.templates.error.invalid_event_type', ['value' => $eventTypeValue]);
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
                $errors[] = "Rad {$rowNum}: " . t('error.generic');
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

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $templateModel = new EventTemplate();

        switch ($action) {
            case 'create':
                $label = trim($_POST['label'] ?? '');
                $eventTypeId = (int)($_POST['event_type_id'] ?? 0);
                $unitId = trim($_POST['unit_id'] ?? '') ?: null;
                $targetUnitId = trim($_POST['target_unit_id'] ?? '') ?: null;
                $isReusable = isset($_POST['is_reusable']) ? 1 : 0;
                $notes = trim($_POST['notes'] ?? '');

                // Validering
                $errors = [];
                if (empty($label)) {
                    $errors[] = t('partner.templates.error.label_required');
                }
                if ($eventTypeId <= 0) {
                    $errors[] = t('partner.templates.error.event_type_required');
                }

                if (empty($errors)) {
                    $templateId = $templateModel->create($organizationId, $eventTypeId, $label, [
                        'unit_id' => $unitId,
                        'target_unit_id' => $targetUnitId,
                        'is_reusable' => $isReusable,
                        'notes' => $notes ?: null,
                        'created_by_user_id' => $userId
                    ]);

                    if ($templateId) {
                        Logger::getInstance()->info('TEMPLATE_CREATE', $userId, "Skapade mall: {$label}");
                        Session::flash('success', t('partner.templates.message.created'));
                        header('Location: templates.php');
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
                $templateId = (int)($_POST['template_id'] ?? 0);
                $label = trim($_POST['label'] ?? '');
                $eventTypeId = (int)($_POST['event_type_id'] ?? 0);
                $unitId = trim($_POST['unit_id'] ?? '') ?: null;
                $targetUnitId = trim($_POST['target_unit_id'] ?? '') ?: null;
                $isReusable = isset($_POST['is_reusable']) ? 1 : 0;
                $notes = trim($_POST['notes'] ?? '');

                // Verifiera att mallen tillhör organisationen
                $existing = $templateModel->findByIdAndOrganization($templateId, $organizationId);
                if (!$existing) {
                    Session::flash('error', t('error.not_found'));
                    header('Location: templates.php');
                    exit;
                }

                // Validering
                $errors = [];
                if (empty($label)) {
                    $errors[] = t('partner.templates.error.label_required');
                }
                if ($eventTypeId <= 0) {
                    $errors[] = t('partner.templates.error.event_type_required');
                }

                if (empty($errors)) {
                    // Uppdatera via raw SQL för att inkludera event_type_id
                    $stmt = $db->prepare("UPDATE event_templates SET label = ?, event_type_id = ?, unit_id = ?, target_unit_id = ?, is_reusable = ?, notes = ? WHERE id = ? AND organization_id = ?");
                    $result = $stmt->execute([$label, $eventTypeId, $unitId, $targetUnitId, $isReusable, $notes ?: null, $templateId, $organizationId]);

                    if ($result) {
                        Logger::getInstance()->info('TEMPLATE_UPDATE', $userId, "Uppdaterade mall: {$label}");
                        Session::flash('success', t('partner.templates.message.updated'));
                        header('Location: templates.php');
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

            case 'delete':
                $templateId = (int)($_POST['template_id'] ?? 0);

                // Verifiera att mallen tillhör organisationen
                $existing = $templateModel->findByIdAndOrganization($templateId, $organizationId);
                if (!$existing) {
                    Session::flash('error', t('error.not_found'));
                    header('Location: templates.php');
                    exit;
                }

                if ($templateModel->delete($templateId)) {
                    Logger::getInstance()->info('TEMPLATE_DELETE', $userId, "Raderade mall: {$existing['label']}");
                    Session::flash('success', t('partner.templates.message.deleted'));
                } else {
                    Session::flash('error', t('error.generic'));
                }
                header('Location: templates.php');
                exit;

            case 'import':
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    $message = t('partner.import.error.no_file');
                    $messageType = 'error';
                } else {
                    $result = processTemplateImport($_FILES['csv_file']['tmp_name'], $organizationId, $eventTypeImportLookup, $unitImportLookup, $userId, $db);
                    if ($result['success']) {
                        $message = t('partner.import.success', ['created' => $result['created'], 'updated' => $result['updated']]);
                        $messageType = 'success';
                        Logger::getInstance()->info('TEMPLATE_IMPORT', $userId, "Importerade mallar: {$result['created']} nya, {$result['updated']} uppdaterade");
                        if (!empty($result['errors'])) {
                            $message .= '<br><br><strong>' . t('common.warnings') . ':</strong><br>' . implode('<br>', $result['errors']);
                        }
                    } else {
                        $message = $result['error'];
                        $messageType = 'error';
                    }
                }
                break;
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

// Hämta alla mallar för denna organisation
$templateModel = new EventTemplate();
$templates = $templateModel->findByOrganization($organizationId);

// Lookup för enheter
$unitLookup = [];
foreach ($units as $u) {
    $unitLookup[$u['id']] = $u['name'];
}

// Lookup för händelsetyper
$eventTypeLookup = [];
foreach ($eventTypes as $et) {
    $eventTypeLookup[$et['id']] = $et;
}

$lang = Language::getInstance()->getLanguage();
$pageTitle = t('partner.templates.title') . ' - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="templates-labels" content='<?= json_encode([
        'label' => t('partner.templates.form.label'),
        'event_type' => t('partner.templates.form.event_type'),
        'select_event_type' => t('partner.templates.form.select_event_type'),
        'unit' => t('partner.templates.form.unit'),
        'select_unit' => t('partner.templates.form.select_unit'),
        'target_unit' => t('partner.templates.form.target_unit'),
        'select_target_unit' => t('partner.templates.form.select_target_unit'),
        'is_reusable' => t('partner.templates.form.is_reusable'),
        'is_reusable_help' => t('partner.templates.form.is_reusable_help'),
        'notes' => t('partner.templates.form.notes'),
        'create' => t('partner.templates.action.create'),
        'update' => t('common.update'),
        'delete' => t('common.delete'),
        'cancel' => t('common.cancel'),
        'import' => t('partner.templates.action.import'),
        'modal_create' => t('partner.templates.modal.create.title'),
        'modal_edit' => t('partner.templates.modal.edit.title'),
        'modal_delete' => t('partner.templates.modal.delete.title'),
        'modal_import' => t('partner.templates.import.title'),
        'confirm_delete' => t('partner.templates.modal.confirm_delete'),
        'import_select_file' => t('partner.import.select_file'),
        'import_file_hint' => t('partner.import.file_hint'),
        'import_columns' => t('partner.import.expected_columns'),
        'import_label_hint' => t('partner.templates.import.label_hint'),
        'import_event_type_hint' => t('partner.templates.import.event_type_hint'),
        'import_unit_hint' => t('partner.templates.import.unit_hint'),
        'import_target_unit_hint' => t('partner.templates.import.target_unit_hint'),
        'import_reusable_hint' => t('partner.templates.import.reusable_hint')
    ]) ?>'>
    <meta name="templates-data" content='<?= htmlspecialchars(json_encode([
        'units' => $units,
        'eventTypes' => array_map(function($et) use ($lang) {
            return [
                'id' => $et['id'],
                'code' => $et['code'],
                'name' => $et["name_{$lang}"] ?? $et['name_sv'],
                'is_transfer' => (bool)$et['is_transfer']
            ];
        }, $eventTypes)
    ]), ENT_QUOTES) ?>'>
    <link rel="stylesheet" href="css/partner.css?v=<?= filemtime(__DIR__ . '/css/partner.css') ?>">
    <link rel="stylesheet" href="../assets/css/modal.css?v=<?= filemtime(__DIR__ . '/../assets/css/modal.css') ?>">
    <script src="../assets/js/modal.js?v=<?= filemtime(__DIR__ . '/../assets/js/modal.js') ?>"></script>
    <script src="../assets/js/qr.js?v=<?= filemtime(__DIR__ . '/../assets/js/qr.js') ?>"></script>
    <script src="js/sidebar.js?v=<?= filemtime(__DIR__ . '/js/sidebar.js') ?>" defer></script>
    <script src="js/modals.js?v=<?= filemtime(__DIR__ . '/js/modals.js') ?>" defer></script>
    <script src="js/templates.js?v=<?= filemtime(__DIR__ . '/js/templates.js') ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h1><?= t('partner.templates.heading') ?></h1>
            <div class="page-actions">
                <div class="search-box">
                    <input type="text" id="table-search" placeholder="<?= t('common.search') ?>...">
                    <button type="button" class="search-clear" title="<?= t('common.cancel') ?>">&times;</button>
                </div>
                <button type="button" class="btn" id="importTemplateBtn"><?= t('partner.templates.action.import') ?></button>
                <a href="export.php?type=templates" class="btn"><?= t('partner.templates.action.export') ?></a>
                <button type="button" class="btn btn-primary" id="createTemplateBtn"><?= t('partner.templates.action.create') ?></button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <table id="templates-table">
                <thead>
                    <tr>
                        <th><?= t('partner.templates.table.label') ?></th>
                        <th><?= t('partner.templates.table.event_type') ?></th>
                        <th><?= t('partner.templates.table.unit') ?></th>
                        <th><?= t('partner.templates.table.target_unit') ?></th>
                        <th><?= t('partner.templates.table.reusable') ?></th>
                        <th><?= t('partner.templates.table.created') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                    <tr>
                        <td colspan="7" class="text-muted text-center"><?= t('partner.templates.list.empty') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($templates as $template):
                        $eventType = $eventTypeLookup[$template['event_type_id']] ?? null;
                        $eventTypeName = $eventType ? ($eventType["name_{$lang}"] ?? $eventType['name_sv']) : '-';
                        $unitName = $template['unit_id'] ? ($unitLookup[$template['unit_id']] ?? '-') : '-';
                        $targetUnitName = $template['target_unit_id'] ? ($unitLookup[$template['target_unit_id']] ?? '-') : '-';
                        $isReusable = $template['is_reusable'] ? t('partner.templates.reusable.yes') : t('partner.templates.reusable.no');

                        $templateData = htmlspecialchars(json_encode([
                            'id' => $template['id'],
                            'label' => $template['label'],
                            'event_type_id' => $template['event_type_id'],
                            'unit_id' => $template['unit_id'],
                            'target_unit_id' => $template['target_unit_id'],
                            'is_reusable' => (bool)$template['is_reusable'],
                            'notes' => $template['notes']
                        ], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

                        // QR-data
                        $qrLines = [
                            t('partner.templates.form.label') . ': ' . $template['label'],
                            t('partner.templates.form.event_type') . ': ' . $eventTypeName
                        ];
                        if ($unitName !== '-') {
                            $qrLines[] = t('partner.templates.form.unit') . ': ' . $unitName;
                        }
                        if ($targetUnitName !== '-') {
                            $qrLines[] = t('partner.templates.form.target_unit') . ': ' . $targetUnitName;
                        }
                        $qrConfig = [
                            'data' => [
                                'type' => 'template',
                                'template_id' => $template['id'],
                                'event_type' => $eventType['code'] ?? 'unknown',
                                'label' => $template['label']
                            ],
                            'title' => $template['label'],
                            'subtitle' => implode("\n", $qrLines),
                            'filename' => 'Template_' . $template['id'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $template['label'])
                        ];
                        $qrData = htmlspecialchars(json_encode($qrConfig, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($template['label']) ?></strong></td>
                        <td><?= htmlspecialchars($eventTypeName) ?></td>
                        <td><?= htmlspecialchars($unitName) ?></td>
                        <td><?= htmlspecialchars($targetUnitName) ?></td>
                        <td><?= $isReusable ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($template['created_at'])) ?></td>
                        <td class="actions">
                            <button type="button" class="btn btn-icon" data-template-edit="<?= $templateData ?>" title="<?= t('partner.templates.action.edit') ?>">✏️</button>
                            <button type="button" class="btn btn-icon" data-qr="<?= $qrData ?>" title="<?= t('partner.templates.action.qr') ?>">📱</button>
                            <button type="button" class="btn btn-icon" data-template-delete="<?= $template['id'] ?>" data-label="<?= htmlspecialchars($template['label']) ?>" title="<?= t('common.delete') ?>">🗑️</button>
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
