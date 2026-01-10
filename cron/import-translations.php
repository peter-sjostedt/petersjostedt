<?php
/**
 * Import translations from CSV
 * Kör: php cron/import-translations.php translations-2025-01-10.csv
 * Eller: php cron/import-translations.php translations-2025-01-10.csv semicolon
 */

if (!isset($argv[1])) {
    die("Användning: php import-translations.php <filnamn.csv> [semicolon]\n");
}

$csvFile = $argv[1];
if (!file_exists($csvFile)) {
    $csvFile = __DIR__ . '/../' . $argv[1];
}
if (!file_exists($csvFile)) {
    die("Filen hittades inte: {$argv[1]}\n");
}

$delimiter = ($argv[2] ?? '') === 'semicolon' ? ';' : ',';

// Läs CSV
$file = fopen($csvFile, 'r');

// Hoppa över BOM om den finns
$bom = fread($file, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($file);
}

// Läs header för språkkoder
$header = fgetcsv($file, 0, $delimiter);
$langCodes = array_slice($header, 1);

// Läs översättningar
$translations = [];
while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
    $key = $row[0];
    $translations[$key] = [];
    foreach ($langCodes as $i => $lang) {
        $translations[$key][$lang] = $row[$i + 1] ?? '';
    }
}
fclose($file);

// Generera PHP-fil
$output = "<?php\n";
$output .= "/**\n";
$output .= " * Översättningar - Importerad " . date('Y-m-d H:i:s') . "\n";
$output .= " */\n\n";
$output .= "return [\n";

foreach ($translations as $key => $texts) {
    $parts = [];
    foreach ($texts as $lang => $text) {
        $escaped = addslashes($text);
        $parts[] = "'$lang' => '$escaped'";
    }
    $output .= "    '$key' => [" . implode(', ', $parts) . "],\n";
}

$output .= "];\n";

// Spara
$targetFile = __DIR__ . '/../config/translations.php';
$backupFile = __DIR__ . '/../config/translations-backup-' . date('Y-m-d-His') . '.php';

// Backup först
copy($targetFile, $backupFile);
echo "Backup skapad: $backupFile\n";

// Skriv ny fil
file_put_contents($targetFile, $output);
echo "Importerat " . count($translations) . " nycklar till translations.php\n";