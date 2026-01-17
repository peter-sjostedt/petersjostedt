<?php
/**
 * Partner Portal - Inleveranser
 * Registrera förväntade leveranser och generera QR för mottagning
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

// Hämta leverantörer från organization_relations
$stmt = $db->prepare("
    SELECT o.id, o.name
    FROM organizations o
    INNER JOIN organization_relations r ON r.partner_org_id = o.id
    WHERE r.organization_id = ? AND r.relation_type = 'supplier' AND r.is_active = 1
    ORDER BY o.name
");
$stmt->execute([$organizationId]);
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generera nästa delivery ID
function generateNextDeliveryId(PDO $db, string $orgId): string
{
    $stmt = $db->prepare("
        SELECT COUNT(*) + 1 as next_num
        FROM events
        WHERE event_type = 'delivery'
        AND organization_id = ?
        AND YEAR(event_at) = YEAR(NOW())
    ");
    $stmt->execute([$orgId]);
    $nextNum = $stmt->fetchColumn();
    return 'DL-' . date('Y') . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

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
                $deliveryId = trim($_POST['delivery_id'] ?? '');
                $purchaseOrderId = trim($_POST['purchase_order_id'] ?? '');
                $supplierOrderId = trim($_POST['supplier_order_id'] ?? '');
                $supplierId = trim($_POST['supplier_id'] ?? '');

                // Hämta befintlig händelse
                $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND organization_id = ? AND event_type = 'delivery'");
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
                if (empty($deliveryId)) {
                    $errors[] = t('partner.deliveries.error.delivery_id_required');
                }
                if (empty($supplierId)) {
                    $errors[] = t('partner.deliveries.error.supplier_required');
                }

                if (empty($errors)) {
                    // Uppdatera metadata
                    $existingMeta['deliveryId'] = $deliveryId;
                    $existingMeta['purchaseOrderId'] = $purchaseOrderId;
                    $existingMeta['supplierOrderId'] = $supplierOrderId;
                    $existingMeta['supplier'] = $supplierId;

                    try {
                        $stmt = $db->prepare("UPDATE events SET metadata = ? WHERE id = ?");
                        $stmt->execute([json_encode($existingMeta, JSON_UNESCAPED_UNICODE), $eventId]);

                        $message = t('partner.deliveries.message.updated', ['id' => $deliveryId]);
                        $messageType = 'success';
                        Logger::getInstance()->info('DELIVERY_UPDATE', $userId, "Uppdaterade inleverans: {$deliveryId}");

                        Session::flash('success', $message);
                        header('Location: deliveries.php');
                        exit;
                    } catch (PDOException $e) {
                        $message = t('error.generic');
                        $messageType = 'error';
                        error_log('Delivery update error: ' . $e->getMessage());
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;

            case 'create':
                $deliveryId = trim($_POST['delivery_id'] ?? '');
                $purchaseOrderId = trim($_POST['purchase_order_id'] ?? '');
                $supplierOrderId = trim($_POST['supplier_order_id'] ?? '');
                $supplierId = trim($_POST['supplier_id'] ?? '');

                // Validering
                $errors = [];
                if (empty($deliveryId)) {
                    $errors[] = t('partner.deliveries.error.delivery_id_required');
                }
                if (empty($supplierId)) {
                    $errors[] = t('partner.deliveries.error.supplier_required');
                }

                if (empty($errors)) {
                    // Skapa metadata JSON
                    $metadata = [
                        'deliveryId' => $deliveryId,
                        'purchaseOrderId' => $purchaseOrderId,
                        'supplierOrderId' => $supplierOrderId,
                        'supplier' => $supplierId,
                        'receiver' => $organizationId,
                        'createdBy' => [
                            'userId' => $userId,
                            'unitId' => null
                        ]
                    ];

                    try {
                        $stmt = $db->prepare("INSERT INTO events (organization_id, event_type, metadata) VALUES (?, 'delivery', ?)");
                        $stmt->execute([$organizationId, json_encode($metadata, JSON_UNESCAPED_UNICODE)]);
                        $eventId = $db->lastInsertId();

                        $message = t('partner.deliveries.message.created', ['id' => $deliveryId]);
                        $messageType = 'success';
                        Logger::getInstance()->info('DELIVERY_CREATE', $userId, "Skapade inleverans: {$deliveryId}");

                        // Redirect för att undvika dubbel POST
                        Session::flash('success', $message);
                        header('Location: deliveries.php');
                        exit;
                    } catch (PDOException $e) {
                        $message = t('error.generic');
                        $messageType = 'error';
                        error_log('Delivery create error: ' . $e->getMessage());
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $eventId = (int)($_POST['event_id'] ?? 0);

                $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND organization_id = ? AND event_type = 'delivery'");
                $stmt->execute([$eventId, $organizationId]);
                $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$existingEvent) {
                    Session::flash('error', t('error.not_found'));
                    header('Location: deliveries.php');
                    exit;
                }

                // Kontrollera om eventet har inträffat
                if (!empty($existingEvent['event_at'])) {
                    Session::flash('error', t('error.cannot_delete_occurred'));
                    header('Location: deliveries.php');
                    exit;
                }

                $existingMeta = json_decode($existingEvent['metadata'], true) ?: [];
                $deliveryId = $existingMeta['deliveryId'] ?? 'Inleverans';

                try {
                    $stmt = $db->prepare("DELETE FROM events WHERE id = ? AND organization_id = ?");
                    $stmt->execute([$eventId, $organizationId]);

                    $message = t('partner.deliveries.message.deleted');
                    $messageType = 'success';
                    Logger::getInstance()->info('DELIVERY_DELETE', $userId, "Raderade inleverans: {$deliveryId}");

                    Session::flash('success', $message);
                    header('Location: deliveries.php');
                    exit;
                } catch (PDOException $e) {
                    $message = t('error.generic');
                    $messageType = 'error';
                    error_log('Delivery delete error: ' . $e->getMessage());
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

// Hämta alla inleveranser för denna organisation
$stmt = $db->prepare("
    SELECT e.id, e.organization_id, e.metadata, e.event_at, e.created_at
    FROM events e
    WHERE e.event_type = 'delivery'
    AND e.organization_id = ?
    ORDER BY e.event_at DESC
");
$stmt->execute([$organizationId]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generera nästa ID för modal
$suggestedDeliveryId = generateNextDeliveryId($db, $organizationId);

$pageTitle = t('partner.deliveries.title') . ' - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="delivery-labels" content='<?= json_encode([
        'delivery_id' => t('partner.deliveries.form.delivery_id'),
        'delivery_id_help' => t('partner.deliveries.form.delivery_id_help'),
        'purchase_order_id' => t('partner.deliveries.form.purchase_order_id'),
        'purchase_order_id_help' => t('partner.deliveries.form.purchase_order_id_help'),
        'supplier_order_id' => t('partner.deliveries.form.supplier_order_id'),
        'supplier_order_id_help' => t('partner.deliveries.form.supplier_order_id_help'),
        'supplier' => t('partner.deliveries.form.supplier'),
        'select_supplier' => t('partner.deliveries.form.select_supplier'),
        'no_suppliers' => t('partner.deliveries.form.no_suppliers'),
        'receiver' => t('partner.deliveries.form.receiver'),
        'create' => t('partner.deliveries.action.create'),
        'update' => t('partner.deliveries.action.update'),
        'delete' => t('common.delete'),
        'cancel' => t('common.cancel'),
        'modal_create' => t('partner.deliveries.modal.create.title'),
        'modal_edit' => t('partner.deliveries.modal.edit.title'),
        'modal_delete' => t('partner.deliveries.modal.delete.title'),
        'confirm_delete' => t('partner.deliveries.modal.confirm_delete'),
        'created' => t('common.created')
    ]) ?>'>
    <meta name="delivery-data" content='<?= htmlspecialchars(json_encode([
        'suggestedId' => $suggestedDeliveryId,
        'suppliers' => $suppliers,
        'receiver' => [
            'id' => $organizationId,
            'name' => $organization['name']
        ]
    ]), ENT_QUOTES) ?>'>
    <link rel="stylesheet" href="css/partner.css?v=<?= filemtime(__DIR__ . '/css/partner.css') ?>">
    <link rel="stylesheet" href="../assets/css/modal.css?v=<?= filemtime(__DIR__ . '/../assets/css/modal.css') ?>">
    <script src="../assets/js/modal.js?v=<?= filemtime(__DIR__ . '/../assets/js/modal.js') ?>"></script>
    <script src="../assets/js/qr.js?v=<?= filemtime(__DIR__ . '/../assets/js/qr.js') ?>"></script>
    <script src="js/sidebar.js?v=<?= filemtime(__DIR__ . '/js/sidebar.js') ?>" defer></script>
    <script src="js/modals.js?v=<?= filemtime(__DIR__ . '/js/modals.js') ?>" defer></script>
    <script src="js/deliveries.js?v=<?= filemtime(__DIR__ . '/js/deliveries.js') ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h1><?= t('partner.deliveries.heading') ?></h1>
            <div class="page-actions">
                <div class="search-box">
                    <input type="text" id="table-search" placeholder="<?= t('common.search') ?>...">
                    <button type="button" class="search-clear" title="<?= t('common.cancel') ?>">&times;</button>
                </div>
                <a href="import.php?type=deliveries" class="btn"><?= t('partner.deliveries.action.import') ?></a>
                <a href="export.php?type=deliveries" class="btn"><?= t('partner.deliveries.action.export') ?></a>
                <button type="button" class="btn btn-primary" id="createDeliveryBtn"><?= t('partner.deliveries.action.create') ?></button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <table id="deliveries-table">
                <thead>
                    <tr>
                        <th><?= t('partner.deliveries.table.delivery_id') ?></th>
                        <th><?= t('partner.deliveries.table.supplier') ?></th>
                        <th><?= t('partner.deliveries.table.order') ?></th>
                        <th><?= t('partner.deliveries.table.created') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deliveries)): ?>
                    <tr>
                        <td colspan="5" class="text-muted text-center"><?= t('partner.deliveries.list.empty') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($deliveries as $delivery):
                        $meta = json_decode($delivery['metadata'], true);

                        // Hämta leverantörsnamn
                        $supplierName = $meta['supplier'] ?? '-';
                        foreach ($suppliers as $supplier) {
                            if ($supplier['id'] === $meta['supplier']) {
                                $supplierName = $supplier['name'];
                                break;
                            }
                        }

                        $deliveryData = htmlspecialchars(json_encode(array_merge($meta, ['id' => $delivery['id'], 'event_at' => $delivery['event_at']]), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

                        // QR-data för mottagning
                        $qrLines = [
                            t('partner.deliveries.qr.from') . ': ' . $supplierName,
                            t('partner.deliveries.qr.to') . ': ' . $organization['name']
                        ];
                        if (!empty($meta['purchaseOrderId'])) {
                            $qrLines[] = t('partner.deliveries.form.purchase_order_id') . ': ' . $meta['purchaseOrderId'];
                        }
                        if (!empty($meta['supplierOrderId'])) {
                            $qrLines[] = t('partner.deliveries.form.supplier_order_id') . ': ' . $meta['supplierOrderId'];
                        }
                        $qrConfig = [
                            'data' => [
                                'type' => 'receive',
                                'delivery_id' => $meta['deliveryId'] ?? 'DL-' . $delivery['id'],
                                'event_id' => $delivery['id'],
                                'from_org' => $meta['supplier'] ?? null,
                                'to_org' => $organizationId,
                                'purchase_order' => $meta['purchaseOrderId'] ?? null,
                                'supplier_order' => $meta['supplierOrderId'] ?? null
                            ],
                            'title' => $meta['deliveryId'] ?? 'DL-' . $delivery['id'],
                            'subtitle' => implode("\n", $qrLines),
                            'filename' => 'Receive_' . ($meta['deliveryId'] ?? 'DL-' . $delivery['id'])
                        ];
                        $qrData = htmlspecialchars(json_encode($qrConfig, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($meta['deliveryId'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($supplierName) ?></td>
                        <td><?= htmlspecialchars($meta['purchaseOrderId'] ?? '-') ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($delivery['created_at'])) ?></td>
                        <td class="actions">
                            <button type="button" class="btn btn-icon" data-delivery-edit="<?= $deliveryData ?>" title="<?= t('partner.deliveries.action.edit') ?>">✏️</button>
                            <button type="button" class="btn btn-icon" data-qr="<?= $qrData ?>" title="<?= t('partner.deliveries.action.qr') ?>">📱</button>
                            <button type="button" class="btn btn-icon" data-delivery-delete="<?= $delivery['id'] ?>" data-label="<?= htmlspecialchars($meta['deliveryId'] ?? '') ?>" title="<?= t('common.delete') ?>">🗑️</button>
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
