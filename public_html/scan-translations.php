<?php
/**
 * Skanna efter saknade översättningar
 *
 * Detta script skannar igenom alla PHP-filer och letar efter användning av t() funktionen
 * för att hitta saknade översättningar
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/../src/LanguageScanner.php';

secure_session_start();
set_security_headers();

// Endast för utvecklingsmiljö
$appConfig = require __DIR__ . '/../config/app.php';
if ($appConfig['environment'] !== 'development') {
    die('Detta verktyg är endast tillgängligt i utvecklingsmiljö');
}

$scanner = new LanguageScanner();
$baseDir = dirname(__DIR__);

// Skanna public_html och admin.petersjostedt.se
$publicFiles = $scanner->scanDirectory($baseDir . '/public_html');
$adminFiles = $scanner->scanDirectory($baseDir . '/admin.petersjostedt.se');

$allKeys = array_merge($publicFiles, $adminFiles);
$uniqueKeys = array_unique($allKeys);
sort($uniqueKeys);

// Ladda befintliga översättningar
$translations = require __DIR__ . '/../config/translations.php';
$existingSv = $translations['sv'] ?? [];
$existingEn = $translations['en'] ?? [];

// Hitta saknade nycklar
$missingSv = [];
$missingEn = [];

foreach ($uniqueKeys as $key) {
    $value = $scanner->getNestedValue($existingSv, $key);
    if ($value === null) {
        $missingSv[] = $key;
    }

    $value = $scanner->getNestedValue($existingEn, $key);
    if ($value === null) {
        $missingEn[] = $key;
    }
}

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Översättningsscanner</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { margin-bottom: 0.5rem; color: #333; }
        .subtitle { color: #666; margin-bottom: 2rem; }
        h2 { margin-bottom: 1rem; color: #555; font-size: 1.3rem; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
        }
        .stat strong {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        .stat span {
            font-size: 1.5rem;
            color: #333;
            font-weight: 600;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .key-list {
            list-style: none;
            padding: 0;
        }
        .key-list li {
            padding: 0.5rem;
            margin: 0.25rem 0;
            background: #f8f9fa;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        code {
            background: #f4f4f4;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Översättningsscanner</h1>
        <p class="subtitle">Skanna igenom projektet och hitta saknade översättningar</p>

        <!-- Statistik -->
        <div class="stats">
            <div class="stat">
                <strong>Totalt antal nycklar</strong>
                <span><?= count($uniqueKeys) ?></span>
            </div>
            <div class="stat">
                <strong>Saknas på svenska</strong>
                <span><?= count($missingSv) ?></span>
            </div>
            <div class="stat">
                <strong>Saknas på engelska</strong>
                <span><?= count($missingEn) ?></span>
            </div>
        </div>

        <!-- Svenska saknade -->
        <div class="card">
            <h2>Saknade svenska översättningar (<?= count($missingSv) ?>)</h2>

            <?php if (empty($missingSv)): ?>
                <div class="success">
                    ✓ Alla nycklar har svenska översättningar!
                </div>
            <?php else: ?>
                <div class="warning">
                    ⚠️ Följande nycklar saknar svenska översättningar:
                </div>
                <ul class="key-list">
                    <?php foreach ($missingSv as $key): ?>
                        <li><?= htmlspecialchars($key) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Engelska saknade -->
        <div class="card">
            <h2>Saknade engelska översättningar (<?= count($missingEn) ?>)</h2>

            <?php if (empty($missingEn)): ?>
                <div class="success">
                    ✓ Alla nycklar har engelska översättningar!
                </div>
            <?php else: ?>
                <div class="warning">
                    ⚠️ Följande nycklar saknar engelska översättningar:
                </div>
                <ul class="key-list">
                    <?php foreach ($missingEn as $key): ?>
                        <li><?= htmlspecialchars($key) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Alla nycklar -->
        <div class="card">
            <h2>Alla hittade översättningsnycklar (<?= count($uniqueKeys) ?>)</h2>
            <ul class="key-list">
                <?php foreach ($uniqueKeys as $key): ?>
                    <li><?= htmlspecialchars($key) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p style="text-align: center; color: #666; margin-top: 2rem;">
            <a href="language-usage.php">→ Språkhantering</a>
        </p>
    </div>
</body>
</html>
