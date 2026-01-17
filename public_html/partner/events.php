<?php
/**
 * Partner Portal - Händelselogg
 * Visar alla händelser (rfid_link, inventory, etc.)
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

// Filter
$filterType = $_GET['type'] ?? '';
$filterDate = $_GET['date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Händelsetyper som visas här (repetitiva händelser)
$eventTypes = [
    'rfid_link' => ['label' => t('partner.events.type.rfid_link'), 'icon' => '🔗'],
    'rfid_register' => ['label' => t('partner.events.type.rfid_register'), 'icon' => '🏷️'],
    'inventory' => ['label' => t('partner.events.type.inventory'), 'icon' => '📋'],
    'receive' => ['label' => t('partner.events.type.receive'), 'icon' => '📥'],
];

// Bygg query
$whereConditions = ["e.organization_id = ?"];
$params = [$organizationId];

// Exkludera shipment och delivery (de har egna sidor)
$whereConditions[] = "e.event_type NOT IN ('shipment', 'delivery')";

if ($filterType && isset($eventTypes[$filterType])) {
    $whereConditions[] = "e.event_type = ?";
    $params[] = $filterType;
}

if ($filterDate) {
    $whereConditions[] = "DATE(e.event_at) = ?";
    $params[] = $filterDate;
}

$whereClause = implode(' AND ', $whereConditions);

// Räkna totalt
$countStmt = $db->prepare("SELECT COUNT(*) FROM events e WHERE $whereClause");
$countStmt->execute($params);
$totalEvents = $countStmt->fetchColumn();
$totalPages = ceil($totalEvents / $perPage);

// Hämta händelser
$stmt = $db->prepare("
    SELECT e.id, e.event_type, e.metadata, e.event_at, e.created_at
    FROM events e
    WHERE $whereClause
    ORDER BY e.event_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hämta artiklar för lookup
$stmt = $db->prepare("SELECT id, sku FROM articles WHERE organization_id = ?");
$stmt->execute([$organizationId]);
$articles = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $articles[$a['id']] = $a['sku'];
}

// Hämta enheter för lookup
$stmt = $db->prepare("SELECT id, name FROM units WHERE organization_id = ?");
$stmt->execute([$organizationId]);
$units = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
    $units[$u['id']] = $u['name'];
}

// Statistik för idag
$todayStmt = $db->prepare("
    SELECT event_type, COUNT(*) as count
    FROM events
    WHERE organization_id = ?
    AND event_type NOT IN ('shipment', 'delivery')
    AND DATE(event_at) = CURDATE()
    GROUP BY event_type
");
$todayStmt->execute([$organizationId]);
$todayStats = [];
foreach ($todayStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $todayStats[$row['event_type']] = $row['count'];
}

$pageTitle = t('partner.events.title') . ' - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="event-labels" content='<?= json_encode([
        'rfid_link' => t('partner.events.type.rfid_link'),
        'rfid_register' => t('partner.events.type.rfid_register'),
        'inventory' => t('partner.events.type.inventory'),
        'receive' => t('partner.events.type.receive'),
        'article' => t('partner.events.detail.article'),
        'rfid' => t('partner.events.detail.rfid'),
        'unit' => t('partner.events.detail.unit'),
        'user' => t('partner.events.detail.user'),
        'timestamp' => t('partner.events.detail.timestamp'),
        'close' => t('common.cancel')
    ]) ?>'>
    <link rel="stylesheet" href="css/partner.css?v=<?= filemtime(__DIR__ . '/css/partner.css') ?>">
    <link rel="stylesheet" href="../assets/css/modal.css?v=<?= filemtime(__DIR__ . '/../assets/css/modal.css') ?>">
    <script src="../assets/js/modal.js?v=<?= filemtime(__DIR__ . '/../assets/js/modal.js') ?>"></script>
    <script src="js/sidebar.js?v=<?= filemtime(__DIR__ . '/js/sidebar.js') ?>" defer></script>
    <script src="js/events.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h1><?= t('partner.events.heading') ?></h1>
            <div class="page-actions">
                <form method="GET" class="filter-form">
                    <select name="type" onchange="this.form.submit()">
                        <option value=""><?= t('partner.events.filter.all_types') ?></option>
                        <?php foreach ($eventTypes as $typeKey => $typeInfo): ?>
                        <option value="<?= $typeKey ?>" <?= $filterType === $typeKey ? 'selected' : '' ?>>
                            <?= $typeInfo['icon'] ?> <?= $typeInfo['label'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" onchange="this.form.submit()">
                    <?php if ($filterType || $filterDate): ?>
                    <a href="events.php" class="btn btn-small"><?= t('common.clear') ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Statistik för idag -->
        <div class="stats">
            <?php foreach ($eventTypes as $typeKey => $typeInfo): ?>
            <div class="stat-card">
                <div class="stat-value"><?= $todayStats[$typeKey] ?? 0 ?></div>
                <div class="stat-label"><?= $typeInfo['icon'] ?> <?= $typeInfo['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <table id="events-table">
                <thead>
                    <tr>
                        <th><?= t('partner.events.table.type') ?></th>
                        <th><?= t('partner.events.table.description') ?></th>
                        <th><?= t('partner.events.table.unit') ?></th>
                        <th><?= t('partner.events.table.timestamp') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="5" class="text-muted text-center"><?= t('partner.events.list.empty') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($events as $event):
                        $meta = json_decode($event['metadata'], true) ?: [];
                        $typeInfo = $eventTypes[$event['event_type']] ?? ['label' => $event['event_type'], 'icon' => '📌'];

                        // Bygg beskrivning baserat på typ
                        $description = '';
                        switch ($event['event_type']) {
                            case 'rfid_link':
                                $sku = $meta['sku'] ?? ($articles[$meta['article_id'] ?? ''] ?? '-');
                                $rfid = $meta['rfid'] ?? '-';
                                $description = "SKU: {$sku} → RFID: " . substr($rfid, -8);
                                break;
                            case 'rfid_register':
                                $rfid = $meta['rfid'] ?? '-';
                                $description = "RFID: " . substr($rfid, -8);
                                break;
                            case 'inventory':
                                $count = $meta['count'] ?? 0;
                                $description = t('partner.events.desc.inventory', ['count' => $count]);
                                break;
                            case 'receive':
                                $deliveryId = $meta['delivery_id'] ?? '-';
                                $description = "ID: {$deliveryId}";
                                break;
                            default:
                                $description = json_encode($meta);
                        }

                        $unitName = isset($meta['unit_id']) ? ($units[$meta['unit_id']] ?? $meta['unit_id']) : '-';

                        $eventData = htmlspecialchars(json_encode(array_merge($meta, [
                            'id' => $event['id'],
                            'event_type' => $event['event_type'],
                            'event_at' => $event['event_at']
                        ]), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?= $event['event_type'] ?>">
                                <?= $typeInfo['icon'] ?> <?= $typeInfo['label'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($description) ?></td>
                        <td><?= htmlspecialchars($unitName) ?></td>
                        <td><?= date('Y-m-d H:i:s', strtotime($event['event_at'])) ?></td>
                        <td class="actions">
                            <button type="button" class="btn btn-icon" data-event-view="<?= $eventData ?>" title="<?= t('common.view') ?>">👁️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-small">&laquo; <?= t('common.previous') ?></a>
                <?php endif; ?>

                <span class="pagination-info">
                    <?= t('partner.events.pagination', ['page' => $page, 'total' => $totalPages]) ?>
                </span>

                <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-small"><?= t('common.next') ?> &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
