<?php
/**
 * Partner Portal - Import av artiklar från CSV
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

if (!Session::isLoggedIn() || !Session::isOrgAdmin()) {
    header('Location: login.php');
    exit;
}

$organizationId = Session::getOrganizationId();

if (!$organizationId) {
    Session::flash('error', t('error.unauthorized'));
    header('Location: index.php');
    exit;
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

// Hantera uppladdning
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $result = ['success' => false, 'error' => t('error.csrf')];
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $result = ['success' => false, 'error' => 'Ingen fil uppladdad eller fel vid uppladdning'];
    } else {
        $result = processImport($_FILES['csv_file']['tmp_name'], $organizationId, $articleSchema, $db);
    }
}

/**
 * Bearbeta CSV-import
 */
function processImport(string $filePath, string $orgId, array $articleSchema, PDO $db): array
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

$pageTitle = t('partner.import.title');
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
            <a href="articles.php" class="btn"><?= t('common.back') ?></a>
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
                <ul>
                    <li><strong>SKU</strong> (<?= t('partner.import.sku_hint') ?>)</li>
                    <?php foreach ($articleSchema as $field): ?>
                    <li><?= htmlspecialchars($field['label']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="text-muted"><?= t('partner.import.update_hint') ?></p>
            </div>
        </div>
    </main>
</body>
</html>
