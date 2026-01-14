<?php
/**
 * Partner Portal - Export av artiklar till CSV
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

$type = $_GET['type'] ?? '';

if ($type !== 'articles') {
    Session::flash('error', 'Ogiltig exporttyp');
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

$db = Database::getInstance()->getPdo();

// Hämta artiklar
$stmt = $db->prepare("SELECT * FROM articles WHERE organization_id = ? ORDER BY sku");
$stmt->execute([$organizationId]);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Skapa filnamn
$safeOrgName = preg_replace('/[^a-zA-Z0-9]/', '_', $organization['name'] ?? 'export');
$filename = $safeOrgName . '_artiklar_' . date('Y-m-d') . '.csv';

// Sätt headers för nedladdning
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Öppna output stream
$output = fopen('php://output', 'w');

// BOM för Excel UTF-8
fwrite($output, "\xEF\xBB\xBF");

// Skriv rubrikrad
$headers = ['SKU'];
foreach ($articleSchema as $field) {
    $headers[] = $field['label'];
}
fputcsv($output, $headers, ',');

// Skriv artikelrader
foreach ($articles as $article) {
    $data = json_decode($article['data'] ?? '{}', true) ?: [];

    $row = [$article['sku']];
    foreach ($articleSchema as $field) {
        $key = fieldKey($field['label']);
        $row[] = $data[$key] ?? '';
    }

    fputcsv($output, $row, ',');
}

fclose($output);
exit;
