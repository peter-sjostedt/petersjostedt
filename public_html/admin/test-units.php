<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

echo "Config loaded OK<br>";

Session::start();
echo "Session started OK<br>";

$db = Database::getInstance()->getPdo();
echo "Database connected OK<br>";

$lang = Language::getInstance();
echo "Language loaded: " . $lang->getLanguage() . "<br>";

// Test translation
echo "Translation test: " . t('admin.units.title') . "<br>";
echo "Translation test 2: " . t('admin.units.table.name') . "<br>";

// Test database
$org_id = 'SE10500101-1234';
$stmt = $db->prepare("SELECT * FROM organizations WHERE id = ?");
$stmt->execute([$org_id]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

if ($org) {
    echo "Organization found: " . htmlspecialchars($org['name']) . "<br>";
} else {
    echo "Organization NOT found<br>";
}

// Test units query
$stmt = $db->prepare("SELECT * FROM units WHERE organization_id = ? ORDER BY name ASC");
$stmt->execute([$org_id]);
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Units found: " . count($units) . "<br>";

echo "<br>All tests passed!";
?>
