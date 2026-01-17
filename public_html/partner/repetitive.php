<?php
/**
 * Partner Portal - Repetitiva händelser
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

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update':
                $eventId = (int)($_POST['event_id'] ?? 0);
                $label = trim($_POST['label'] ?? '');
                $unitId = trim($_POST['unit_id'] ?? '') ?: null;
                $notes = trim($_POST['notes'] ?? '');

                // Hämta befintlig händelse
                $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND organization_id = ? AND event_type = 'repetitive'");
                $stmt->execute([$eventId, $organizationId]);
                $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$existingEvent) {
                    $message = t('error.not_found');
                    $messageType = 'error';
                    break;
                }

                $existingMeta = json_decode($existingEvent['metadata'], true) ?: [];

                // Validering
                $errors = [];
                if (empty($label)) {
                    $errors[] = t('partner.repetitive.error.label_required');
                }

                if (empty($errors)) {
                    // Uppdatera metadata
                    $existingMeta['label'] = $label;
                    $existingMeta['unit_id'] = $unitId;
                    $existingMeta['notes'] = $notes;

                    try {
                        $stmt = $db->prepare("UPDATE events SET metadata = ? WHERE id = ?");
                        $stmt->execute([json_encode($existingMeta, JSON_UNESCAPED_UNICODE), $eventId]);

                        $message = t('partner.repetitive.message.updated');
                        $messageType = 'success';
                        Logger::getInstance()->info('REPETITIVE_UPDATE', $userId, "Uppdaterade händelse: {$label}");

                        Session::flash('success', $message);
                        header('Location: repetitive.php');
                        exit;
                    } catch (PDOException $e) {
                        $message = t('error.generic');
                        $messageType = 'error';
                        error_log('Repetitive update error: ' . $e->getMessage());
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;

            case 'create':
                $label = trim($_POST['label'] ?? '');
                $unitId = trim($_POST['unit_id'] ?? '') ?: null;
                $notes = trim($_POST['notes'] ?? '');

                // Validering
                $errors = [];
                if (empty($label)) {
                    $errors[] = t('partner.repetitive.error.label_required');
                }

                if (empty($errors)) {
                    // Skapa metadata JSON
                    $metadata = [
                        'label' => $label,
                        'createdBy' => [
                            'userId' => $userId,
                            'unitId' => $unitId
                        ]
                    ];

                    if ($unitId) {
                        $metadata['unit_id'] = $unitId;
                    }
                    if ($notes) {
                        $metadata['notes'] = $notes;
                    }

                    try {
                        $stmt = $db->prepare("INSERT INTO events (organization_id, event_type, metadata) VALUES (?, 'repetitive', ?)");
                        $stmt->execute([$organizationId, json_encode($metadata, JSON_UNESCAPED_UNICODE)]);

                        $message = t('partner.repetitive.message.created');
                        $messageType = 'success';
                        Logger::getInstance()->info('REPETITIVE_CREATE', $userId, "Skapade händelse: {$label}");

                        Session::flash('success', $message);
                        header('Location: repetitive.php');
                        exit;
                    } catch (PDOException $e) {
                        $message = t('error.generic');
                        $messageType = 'error';
                        error_log('Repetitive create error: ' . $e->getMessage());
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $eventId = (int)($_POST['event_id'] ?? 0);

                // Hämta befintlig händelse
                $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND organization_id = ? AND event_type = 'repetitive'");
                $stmt->execute([$eventId, $organizationId]);
                $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$existingEvent) {
                    Session::flash('error', t('error.not_found'));
                    header('Location: repetitive.php');
                    exit;
                }

                // Kontrollera om eventet har inträffat
                if (!empty($existingEvent['event_at'])) {
                    Session::flash('error', t('error.cannot_delete_occurred'));
                    header('Location: repetitive.php');
                    exit;
                }

                $existingMeta = json_decode($existingEvent['metadata'], true) ?: [];
                $label = $existingMeta['label'] ?? 'Händelse';

                try {
                    $stmt = $db->prepare("DELETE FROM events WHERE id = ? AND organization_id = ?");
                    $stmt->execute([$eventId, $organizationId]);

                    $message = t('partner.repetitive.message.deleted');
                    $messageType = 'success';
                    Logger::getInstance()->info('REPETITIVE_DELETE', $userId, "Raderade händelse: {$label}");

                    Session::flash('success', $message);
                    header('Location: repetitive.php');
                    exit;
                } catch (PDOException $e) {
                    $message = t('error.generic');
                    $messageType = 'error';
                    error_log('Repetitive delete error: ' . $e->getMessage());
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

// Hämta alla repetitiva händelser för denna organisation
$stmt = $db->prepare("
    SELECT e.id, e.event_type, e.metadata, e.event_at, e.created_at
    FROM events e
    WHERE e.event_type = 'repetitive'
    AND e.organization_id = ?
    ORDER BY e.event_at DESC
");
$stmt->execute([$organizationId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lookup för enheter
$unitLookup = [];
foreach ($units as $u) {
    $unitLookup[$u['id']] = $u['name'];
}

$pageTitle = t('partner.repetitive.title') . ' - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="repetitive-labels" content='<?= json_encode([
        'label' => t('partner.repetitive.form.label'),
        'unit' => t('partner.repetitive.form.unit'),
        'select_unit' => t('partner.repetitive.form.select_unit'),
        'notes' => t('partner.repetitive.form.notes'),
        'created' => t('partner.repetitive.table.created'),
        'create' => t('partner.repetitive.action.create'),
        'update' => t('partner.repetitive.action.update'),
        'delete' => t('common.delete'),
        'cancel' => t('common.cancel'),
        'modal_create' => t('partner.repetitive.modal.create.title'),
        'modal_edit' => t('partner.repetitive.modal.edit.title'),
        'modal_delete' => t('partner.repetitive.modal.delete.title'),
        'confirm_delete' => t('partner.repetitive.modal.confirm_delete')
    ]) ?>'>
    <meta name="repetitive-data" content='<?= htmlspecialchars(json_encode([
        'units' => $units
    ]), ENT_QUOTES) ?>'>
    <link rel="stylesheet" href="css/partner.css?v=<?= filemtime(__DIR__ . '/css/partner.css') ?>">
    <link rel="stylesheet" href="../assets/css/modal.css?v=<?= filemtime(__DIR__ . '/../assets/css/modal.css') ?>">
    <script src="../assets/js/modal.js?v=<?= filemtime(__DIR__ . '/../assets/js/modal.js') ?>"></script>
    <script src="../assets/js/qr.js?v=<?= filemtime(__DIR__ . '/../assets/js/qr.js') ?>"></script>
    <script src="js/sidebar.js?v=<?= filemtime(__DIR__ . '/js/sidebar.js') ?>" defer></script>
    <script src="js/modals.js?v=<?= filemtime(__DIR__ . '/js/modals.js') ?>" defer></script>
    <script src="js/repetitive.js?v=<?= filemtime(__DIR__ . '/js/repetitive.js') ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h1><?= t('partner.repetitive.heading') ?></h1>
            <div class="page-actions">
                <div class="search-box">
                    <input type="text" id="table-search" placeholder="<?= t('common.search') ?>...">
                    <button type="button" class="search-clear" title="<?= t('common.cancel') ?>">&times;</button>
                </div>
                <a href="import.php?type=repetitive" class="btn"><?= t('partner.repetitive.action.import') ?></a>
                <a href="export.php?type=repetitive" class="btn"><?= t('partner.repetitive.action.export') ?></a>
                <button type="button" class="btn btn-primary" id="createRepetitiveBtn"><?= t('partner.repetitive.action.create') ?></button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <table id="repetitive-table">
                <thead>
                    <tr>
                        <th><?= t('partner.repetitive.table.label') ?></th>
                        <th><?= t('partner.repetitive.table.unit') ?></th>
                        <th><?= t('partner.repetitive.table.notes') ?></th>
                        <th><?= t('partner.repetitive.table.created') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="5" class="text-muted text-center"><?= t('partner.repetitive.list.empty') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($events as $event):
                        $meta = json_decode($event['metadata'], true) ?: [];
                        $label = $meta['label'] ?? '-';
                        $unitName = isset($meta['unit_id']) ? ($unitLookup[$meta['unit_id']] ?? '-') : '-';
                        $notes = $meta['notes'] ?? '-';

                        $eventData = htmlspecialchars(json_encode(array_merge($meta, [
                            'id' => $event['id'],
                            'event_at' => $event['event_at']
                        ]), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

                        // QR-data
                        $qrLines = [
                            t('partner.repetitive.form.label') . ': ' . $label
                        ];
                        if ($unitName !== '-') {
                            $qrLines[] = t('partner.repetitive.form.unit') . ': ' . $unitName;
                        }
                        if ($notes !== '-') {
                            $qrLines[] = t('partner.repetitive.form.notes') . ': ' . $notes;
                        }
                        $qrConfig = [
                            'data' => [
                                'type' => 'repetitive',
                                'event_id' => $event['id'],
                                'label' => $label,
                                'unit_id' => $meta['unit_id'] ?? null
                            ],
                            'title' => $label,
                            'subtitle' => implode("\n", $qrLines),
                            'filename' => 'Repetitive_' . $event['id'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $label)
                        ];
                        $qrData = htmlspecialchars(json_encode($qrConfig, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($label) ?></strong></td>
                        <td><?= htmlspecialchars($unitName) ?></td>
                        <td class="truncate"><?= htmlspecialchars($notes) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($event['created_at'])) ?></td>
                        <td class="actions">
                            <button type="button" class="btn btn-icon" data-repetitive-edit="<?= $eventData ?>" title="<?= t('partner.repetitive.action.edit') ?>">✏️</button>
                            <button type="button" class="btn btn-icon" data-qr="<?= $qrData ?>" title="<?= t('partner.repetitive.action.qr') ?>">📱</button>
                            <button type="button" class="btn btn-icon" data-repetitive-delete="<?= $event['id'] ?>" data-label="<?= htmlspecialchars($label) ?>" title="<?= t('common.delete') ?>">🗑️</button>
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
