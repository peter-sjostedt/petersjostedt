<?php
/**
 * Partner Portal - Artikelhantering
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
$message = '';
$messageType = '';

/**
 * Skapa en säker fältnyckel från label (samma logik som i JavaScript)
 */
function fieldKey(string $label): string
{
    $key = mb_strtolower($label);
    $key = str_replace(['å', 'ä', 'ö'], ['a', 'a', 'o'], $key);
    $key = preg_replace('/[^a-z0-9]/', '_', $key);
    $key = preg_replace('/_+/', '_', $key);
    $key = trim($key, '_');
    return $key;
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
        return ['success' => false, 'error' => t('partner.import.error.empty_file')];
    }

    // Parsa rubrikrad
    $headers = str_getcsv(array_shift($lines), $separator);
    $headers = array_map('trim', $headers);

    // Hitta SKU-kolumn
    $skuIndex = array_search('SKU', $headers);
    if ($skuIndex === false) {
        return ['success' => false, 'error' => t('partner.import.error.missing_column', ['column' => 'SKU'])];
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
        foreach ($fieldMap as $csvIndex => $fKey) {
            $fieldData[$fKey] = trim($values[$csvIndex] ?? '');
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
 * Generera SKU baserat på fältvärdena
 * Tar första 3 bokstäverna från varje fält och sammanfogar med bindestreck
 * T.ex. Lakan + Medium + Vit = LAK-MED-VIT
 */
function generateSku(PDO $db, string $orgId, array $fieldData): string
{
    $parts = [];
    foreach ($fieldData as $value) {
        if (!empty($value)) {
            $part = mb_strtoupper(mb_substr(trim($value), 0, 3));
            if (!empty($part)) {
                $parts[] = $part;
            }
        }
    }

    if (empty($parts)) {
        $baseSku = 'ART';
    } else {
        $baseSku = implode('-', $parts);
    }

    // Kontrollera unikhet, lägg till nummer om det behövs
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

// Hantera åtgärder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // Hämta artikelfält för att veta vilka nycklar som ska samlas
        $schemaFields = $organization['article_schema'] ?? [];

        switch ($action) {
            case 'create':
                $sku = trim($_POST['sku'] ?? '');

                // Samla ihop dynamiska fält baserat på schema (label-baserade nycklar)
                $articleData = [];
                $firstValue = null;
                foreach ($schemaFields as $field) {
                    $key = fieldKey($field['label']);
                    $value = trim($_POST[$key] ?? '');
                    if (!empty($value)) {
                        $articleData[$key] = $value;
                        if ($firstValue === null) {
                            $firstValue = $value;
                        }
                    }
                }

                // Auto-generera SKU från fältvärdena om tomt
                if (empty($sku)) {
                    $sku = generateSku($db, $organizationId, $articleData);
                }

                // Kontrollera om SKU redan finns
                $stmt = $db->prepare("SELECT id FROM articles WHERE organization_id = ? AND sku = ?");
                $stmt->execute([$organizationId, $sku]);
                if ($stmt->fetch()) {
                    $message = t('partner.articles.message.sku_exists');
                    $messageType = 'error';
                } else {
                    $dataJson = !empty($articleData) ? json_encode($articleData) : null;
                    // name = första fältvärdet (t.ex. Artikelnamn), annars SKU
                    $name = $firstValue ?? $sku;
                    $stmt = $db->prepare("INSERT INTO articles (organization_id, sku, name, data) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$organizationId, $sku, $name, $dataJson])) {
                        $message = t('partner.articles.message.created');
                        $messageType = 'success';
                        Logger::getInstance()->info('ARTICLE_CREATE', Session::getUserId(), "Skapade artikel: {$sku}");
                        header('Location: articles.php');
                        exit;
                    } else {
                        $message = t('error.generic');
                        $messageType = 'error';
                    }
                }
                break;

            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                // Samla ihop dynamiska fält baserat på schema (label-baserade nycklar)
                $articleData = [];
                foreach ($schemaFields as $field) {
                    $key = fieldKey($field['label']);
                    $value = trim($_POST[$key] ?? '');
                    if (!empty($value)) {
                        $articleData[$key] = $value;
                    }
                }

                // Verifiera att artikeln tillhör organisationen
                $stmt = $db->prepare("SELECT id, sku FROM articles WHERE id = ? AND organization_id = ?");
                $stmt->execute([$id, $organizationId]);
                $existingArticle = $stmt->fetch();

                if (!$existingArticle) {
                    $message = t('error.unauthorized');
                    $messageType = 'error';
                } else {
                    $dataJson = !empty($articleData) ? json_encode($articleData) : null;
                    $stmt = $db->prepare("UPDATE articles SET data = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND organization_id = ?");
                    if ($stmt->execute([$dataJson, $is_active, $id, $organizationId])) {
                        $message = t('partner.articles.message.updated');
                        $messageType = 'success';
                        Logger::getInstance()->info('ARTICLE_UPDATE', Session::getUserId(), "Uppdaterade artikel: {$existingArticle['sku']}");
                        header('Location: articles.php');
                        exit;
                    } else {
                        $message = t('error.generic');
                        $messageType = 'error';
                    }
                }
                break;

            case 'import':
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    $message = t('partner.import.error.no_file');
                    $messageType = 'error';
                } else {
                    $result = processArticleImport($_FILES['csv_file']['tmp_name'], $organizationId, $schemaFields, $db);
                    if ($result['success']) {
                        $message = t('partner.import.success', ['created' => $result['created'], 'updated' => $result['updated']]);
                        $messageType = 'success';
                        Logger::getInstance()->info('ARTICLE_IMPORT', Session::getUserId(), "Importerade artiklar: {$result['created']} nya, {$result['updated']} uppdaterade");
                    } else {
                        $message = $result['error'];
                        $messageType = 'error';
                    }
                }
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);

                // Verifiera att artikeln tillhör organisationen
                $stmt = $db->prepare("SELECT sku, is_used FROM articles WHERE id = ? AND organization_id = ?");
                $stmt->execute([$id, $organizationId]);
                $article = $stmt->fetch();

                if (!$article) {
                    $message = t('error.unauthorized');
                    $messageType = 'error';
                } elseif ($article['is_used']) {
                    $message = t('partner.articles.message.in_use');
                    $messageType = 'error';
                } else {
                    // Kontrollera om det finns RFID-taggar kopplade
                    $stmt = $db->prepare("SELECT COUNT(*) FROM rfids WHERE article_id = ?");
                    $stmt->execute([$id]);
                    $rfidCount = (int)$stmt->fetchColumn();

                    if ($rfidCount > 0) {
                        $message = t('partner.articles.message.has_rfids', ['count' => $rfidCount]);
                        $messageType = 'error';
                    } else {
                        $stmt = $db->prepare("DELETE FROM articles WHERE id = ? AND organization_id = ?");
                        if ($stmt->execute([$id, $organizationId])) {
                            $message = t('partner.articles.message.deleted');
                            $messageType = 'success';
                            Logger::getInstance()->warning('ARTICLE_DELETE', Session::getUserId(), "Raderade artikel: {$article['sku']}");
                        } else {
                            $message = t('error.generic');
                            $messageType = 'error';
                        }
                    }
                }
                break;
        }
    }
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$validFilters = ['all', 'new', 'used', 'inactive'];
if (!in_array($filter, $validFilters)) {
    $filter = 'all';
}

// Bygg SQL baserat på filter
$sql = "SELECT a.id, a.sku, a.data, a.is_active, a.is_used, a.created_at, a.updated_at,
        COALESCE((SELECT COUNT(*) FROM rfids r WHERE r.article_id = a.id), 0) as rfid_count
        FROM articles a WHERE a.organization_id = ?";
$params = [$organizationId];

switch ($filter) {
    case 'new':
        $sql .= " AND a.is_active = 1 AND a.is_used = 0";
        break;
    case 'used':
        $sql .= " AND a.is_active = 1 AND a.is_used = 1";
        break;
    case 'inactive':
        $sql .= " AND a.is_active = 0";
        break;
}

$sql .= " ORDER BY a.is_active DESC, a.sku";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Räkna per status för filterknapparna
$stmt = $db->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 AND is_used = 0 THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN is_active = 1 AND is_used = 1 THEN 1 ELSE 0 END) as used_count,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count
    FROM articles WHERE organization_id = ?
");
$stmt->execute([$organizationId]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

// Hämta artikelfält från organisationens article_schema
// Organization-modellen avkodar redan JSON till array
$articleFields = $organization['article_schema'] ?? [];

$pageTitle = t('partner.articles.title') . ' - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="article-labels" content='<?= json_encode([
        'sku' => t('partner.articles.form.sku'),
        'sku_help' => t('partner.articles.form.sku_help'),
        'sku_auto' => t('partner.articles.form.sku_auto'),
        'is_active' => t('partner.articles.form.is_active'),
        'create' => t('common.create'),
        'update' => t('common.update'),
        'cancel' => t('common.cancel'),
        'import' => t('partner.articles.action.import'),
        'modal_create' => t('partner.articles.modal.create.title'),
        'modal_edit' => t('partner.articles.modal.edit.title'),
        'modal_import' => t('partner.import.title'),
        'no_fields' => t('partner.articles.form.no_fields'),
        'no_fields_link' => t('partner.articles.form.no_fields_link'),
        'import_select_file' => t('partner.import.select_file'),
        'import_file_hint' => t('partner.import.file_hint'),
        'import_columns' => t('partner.import.expected_columns'),
        'import_sku_hint' => t('partner.import.sku_hint')
    ]) ?>'>
    <meta name="article-fields" content='<?= htmlspecialchars(json_encode($articleFields), ENT_QUOTES) ?>'>
    <link rel="stylesheet" href="css/partner.css?v=<?= filemtime(__DIR__ . '/css/partner.css') ?>">
    <link rel="stylesheet" href="../assets/css/modal.css?v=<?= filemtime(__DIR__ . '/../assets/css/modal.css') ?>">
    <script src="../assets/js/modal.js?v=<?= filemtime(__DIR__ . '/../assets/js/modal.js') ?>"></script>
    <script src="../assets/js/qr.js?v=<?= filemtime(__DIR__ . '/../assets/js/qr.js') ?>"></script>
    <script src="js/sidebar.js?v=<?= filemtime(__DIR__ . '/js/sidebar.js') ?>" defer></script>
    <script src="js/modals.js?v=<?= filemtime(__DIR__ . '/js/modals.js') ?>" defer></script>
    <script src="js/articles.js?v=<?= filemtime(__DIR__ . '/js/articles.js') ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h1><?= t('partner.articles.heading') ?></h1>
            <div class="page-actions">
                <div class="search-box">
                    <input type="text" id="table-search" placeholder="<?= t('common.search') ?>...">
                    <button type="button" class="search-clear" title="<?= t('common.cancel') ?>">&times;</button>
                </div>
                <button type="button" class="btn" id="importArticleBtn"><?= t('partner.articles.action.import') ?></button>
                <a href="export.php?type=articles" class="btn"><?= t('partner.articles.action.export') ?></a>
                <button type="button" class="btn btn-primary" id="createArticleBtn"><?= t('partner.articles.action.create') ?></button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="filter-bar">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                <?= t('filter.all') ?> <span class="count"><?= (int)$counts['total'] ?></span>
            </a>
            <a href="?filter=new" class="filter-btn <?= $filter === 'new' ? 'active' : '' ?>">
                <?= t('partner.articles.filter.new') ?> <span class="count"><?= (int)$counts['new_count'] ?></span>
            </a>
            <a href="?filter=used" class="filter-btn <?= $filter === 'used' ? 'active' : '' ?>">
                <?= t('partner.articles.filter.used') ?> <span class="count"><?= (int)$counts['used_count'] ?></span>
            </a>
            <a href="?filter=inactive" class="filter-btn <?= $filter === 'inactive' ? 'active' : '' ?>">
                <?= t('partner.articles.filter.inactive') ?> <span class="count"><?= (int)$counts['inactive_count'] ?></span>
            </a>
        </div>

        <div class="card">
            <table id="articles-table">
                <thead>
                    <tr>
                        <th><?= t('partner.articles.table.sku') ?></th>
                        <th><?= t('partner.articles.table.status') ?></th>
                        <th><?= t('partner.articles.table.created') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="4" class="text-muted text-center"><?= t('partner.articles.list.empty') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($articles as $article):
                        // QR-data
                        $articleData = json_decode($article['data'] ?? '{}', true) ?: [];
                        $qrLines = ['Org: ' . $organizationId . ' (' . $organization['name'] . ')'];
                        if (!empty($articleData) && !empty($articleFields)) {
                            $sortedValues = [];
                            foreach ($articleFields as $field) {
                                $key = fieldKey($field['label']);
                                if (!empty($articleData[$key])) {
                                    $sortedValues[] = $articleData[$key];
                                }
                            }
                            if (!empty($sortedValues)) {
                                $qrLines[] = implode(' | ', $sortedValues);
                            }
                        }
                        $qrConfig = [
                            'data' => [
                                'type' => 'sku',
                                'org_id' => $organizationId,
                                'sku' => $article['sku']
                            ],
                            'title' => 'SKU: ' . $article['sku'],
                            'subtitle' => implode("\n", $qrLines),
                            'filename' => 'SKU_' . $article['sku']
                        ];
                        $qrData = htmlspecialchars(json_encode($qrConfig, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="<?= !$article['is_active'] ? 'row-inactive' : '' ?>">
                        <td><strong><?= htmlspecialchars($article['sku']) ?></strong></td>
                        <td>
                            <?php if (!$article['is_active']): ?>
                            <span class="badge badge-inactive"><?= t('partner.articles.status.inactive') ?></span>
                            <?php elseif ($article['is_used']): ?>
                            <span class="badge badge-used"><?= t('partner.articles.status.used') ?></span>
                            <?php else: ?>
                            <span class="badge badge-new"><?= t('partner.articles.status.new') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('Y-m-d', strtotime($article['created_at'])) ?></td>
                        <td class="actions">
                            <button type="button" class="btn btn-icon" data-qr="<?= $qrData ?>" title="QR">📱</button>
                            <button type="button" class="btn btn-icon" data-article-edit='<?= htmlspecialchars(json_encode($article)) ?>' title="<?= t('common.edit') ?>">✏️</button>
                            <?php if (!$article['is_used'] && $article['rfid_count'] == 0): ?>
                            <form method="POST" style="display:inline;" data-confirm="<?= t('partner.articles.modal.delete.message', ['sku' => htmlspecialchars($article['sku'])]) ?>">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $article['id'] ?>">
                                <button type="submit" class="btn btn-icon btn-icon-danger" title="<?= t('common.delete') ?>">🗑️</button>
                            </form>
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
