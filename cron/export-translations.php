<?php
/**
 * Export translations to CSV
 * Kör: php cron/export-translations.php
 * Eller: php cron/export-translations.php semicolon
 */

$translations = require __DIR__ . '/../config/translations.php';
$languages = require __DIR__ . '/../config/languages.php';

// Delimiter: semicolon för svenska Excel, comma för standard
$delimiter = ($argv[1] ?? '') === 'semicolon' ? ';' : ',';

// Hämta språkkoder
$langCodes = array_keys($languages);

// Skapa CSV
$filename = __DIR__ . '/../translations-' . date('Y-m-d') . '.csv';
$file = fopen($filename, 'w');

// UTF-8 BOM för Excel-kompatibilitet
fwrite($file, "\xEF\xBB\xBF");

// Header
fputcsv($file, array_merge(['key'], $langCodes), $delimiter);

// Data
foreach ($translations as $key => $texts) {
    $row = [$key];
    foreach ($langCodes as $lang) {
        $row[] = $texts[$lang] ?? '';
    }
    fputcsv($file, $row, $delimiter);
}

fclose($file);

echo "Exporterat " . count($translations) . " nycklar till: $filename\n";
echo "Delimiter: " . ($delimiter === ';' ? 'semikolon' : 'komma') . "\n";