<?php
/**
 * Test - Backup-system
 *
 * Testar backup-funktionalitet: skapa, verifiera, lista, rotera.
 */

require_once __DIR__ . '/includes/config.php';

Session::start();
Session::requireAdmin('login.php');

$backup = Backup::getInstance();
$testResults = [];

// Test 1: Skapa testbackup
$testResults['create'] = $backup->createBackup('daily');

// Test 2: Verifiera backup
if ($testResults['create']['success']) {
    $testResults['verify'] = $backup->verifyBackup($testResults['create']['path']);
} else {
    $testResults['verify'] = ['valid' => false, 'error' => 'Ingen backup att verifiera'];
}

// Test 3: Lista backuper
$testResults['backups'] = $backup->listBackups('all');

// Test 4: Statistik
$testResults['stats'] = $backup->getStats();

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Backup-system</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            background: #f5f5f5;
        }
        h1 {
            color: #16213e;
            border-bottom: 3px solid #e94560;
            padding-bottom: 0.5rem;
        }
        h2 {
            color: #0f3460;
            margin-top: 2rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #e94560;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #16213e;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
        }
        .info-box strong {
            color: #16213e;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #16213e;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-daily { background: #d1ecf1; color: #0c5460; }
        .badge-weekly { background: #d4edda; color: #155724; }
        .badge-monthly { background: #fff3cd; color: #856404; }
        .back-link {
            display: inline-block;
            color: #0f3460;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-link:hover {
            color: #e94560;
        }
        code {
            background: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <a href="../admin.petersjostedt.se/" class="back-link">← Tillbaka till admin</a>

    <h1>Test - Backup-system</h1>

    <div class="card">
        <h2>1. Test: Skapa backup</h2>
        <?php if ($testResults['create']['success']): ?>
            <p class="success">✓ Backup skapad framgångsrikt</p>
            <div class="info-grid">
                <div class="info-box">
                    <strong>Filnamn:</strong><br>
                    <code><?= htmlspecialchars($testResults['create']['filename']) ?></code>
                </div>
                <div class="info-box">
                    <strong>Storlek:</strong><br>
                    <?= htmlspecialchars($testResults['create']['size_human']) ?>
                </div>
                <div class="info-box">
                    <strong>Tid:</strong><br>
                    <?= htmlspecialchars($testResults['create']['duration']) ?>s
                </div>
                <div class="info-box">
                    <strong>Sökväg:</strong><br>
                    <code style="font-size: 0.75rem; word-break: break-all;"><?= htmlspecialchars($testResults['create']['path']) ?></code>
                </div>
            </div>
        <?php else: ?>
            <p class="error">✗ Backup misslyckades</p>
            <p>Fel: <?= htmlspecialchars($testResults['create']['error']) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>2. Test: Verifiera backup</h2>
        <?php if ($testResults['verify']['valid']): ?>
            <p class="success">✓ Backup är giltig</p>
            <div class="info-grid">
                <div class="info-box">
                    <strong>Komprimerad storlek:</strong><br>
                    <?= htmlspecialchars($testResults['verify']['info']['size_human']) ?>
                </div>
                <div class="info-box">
                    <strong>Okomprimerad storlek:</strong><br>
                    <?= htmlspecialchars($testResults['verify']['info']['uncompressed_size_human']) ?>
                </div>
                <div class="info-box">
                    <strong>Kompressionsgrad:</strong><br>
                    <?= htmlspecialchars($testResults['verify']['info']['compression_ratio']) ?>
                </div>
                <div class="info-box">
                    <strong>Tabeller:</strong><br>
                    <?= htmlspecialchars($testResults['verify']['info']['tables']) ?>
                </div>
                <div class="info-box">
                    <strong>INSERT-satser:</strong><br>
                    <?= htmlspecialchars($testResults['verify']['info']['inserts']) ?>
                </div>
            </div>
        <?php else: ?>
            <p class="error">✗ Backup-verifiering misslyckades</p>
            <p>Fel: <?= htmlspecialchars($testResults['verify']['error']) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>3. Test: Backup-statistik</h2>
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-value"><?= $testResults['stats']['total'] ?></div>
                <div class="stat-label">Totalt antal backuper</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $testResults['stats']['daily'] ?></div>
                <div class="stat-label">Dagliga</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $testResults['stats']['weekly'] ?></div>
                <div class="stat-label">Veckovisa</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $testResults['stats']['monthly'] ?></div>
                <div class="stat-label">Månatliga</div>
            </div>
        </div>

        <?php if (isset($testResults['stats']['total_size_human'])): ?>
            <div class="info-box" style="margin-top: 1rem;">
                <p><strong>Total storlek:</strong> <?= htmlspecialchars($testResults['stats']['total_size_human']) ?></p>
                <p><strong>Äldsta backup:</strong> <?= htmlspecialchars($testResults['stats']['oldest']) ?></p>
                <p><strong>Senaste backup:</strong> <?= htmlspecialchars($testResults['stats']['newest']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>4. Test: Lista backuper</h2>
        <?php if (empty($testResults['backups'])): ?>
            <p>Inga backuper hittades.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Filnamn</th>
                        <th>Typ</th>
                        <th>Storlek</th>
                        <th>Skapad</th>
                        <th>Ålder</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testResults['backups'] as $b): ?>
                        <tr>
                            <td><code style="font-size: 0.8rem;"><?= htmlspecialchars($b['name']) ?></code></td>
                            <td><span class="badge badge-<?= $b['type'] ?>"><?= htmlspecialchars($b['type']) ?></span></td>
                            <td><?= htmlspecialchars($b['size_human']) ?></td>
                            <td><?= htmlspecialchars($b['modified_human']) ?></td>
                            <td><?= $b['age_days'] ?> dagar</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>5. Funktioner tillgängliga</h2>
        <ul>
            <li><strong>Automatiska backuper:</strong> Dagliga via cron-jobb</li>
            <li><strong>Tre nivåer:</strong> Daglig (7 dagar), Veckovis (4 veckor), Månatlig (12 månader)</li>
            <li><strong>Gzip-komprimering:</strong> Sparar diskutrymme (typiskt 80-90% kompression)</li>
            <li><strong>Automatisk rotation:</strong> Flyttar dagliga → veckovisa → månatliga</li>
            <li><strong>Verifiering:</strong> Kontrollerar backup-integritet</li>
            <li><strong>Återställning:</strong> Enkelt via admin-panel</li>
            <li><strong>Säkerhet:</strong> HTTP-åtkomst blockerad, CSRF-skydd</li>
        </ul>
    </div>

    <div class="card">
        <h2>6. Cron-jobb för automatiska backuper</h2>
        <p>Lägg till i crontab för automatiska dagliga backuper kl 03:00:</p>
        <pre>0 3 * * * cd <?= dirname(__DIR__) ?> && php cron/backup-database.php</pre>

        <h3 style="margin-top: 1.5rem;">Manuell körning för test:</h3>
        <pre>php <?= dirname(__DIR__) ?>/cron/backup-database.php</pre>
    </div>

    <div class="card">
        <h2>7. Retention Policy</h2>
        <table>
            <thead>
                <tr>
                    <th>Typ</th>
                    <th>Antal som behålls</th>
                    <th>Täcker period</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge badge-daily">Daglig</span></td>
                    <td>7 backuper</td>
                    <td>Senaste veckan</td>
                </tr>
                <tr>
                    <td><span class="badge badge-weekly">Veckovis</span></td>
                    <td>4 backuper</td>
                    <td>Senaste månaden</td>
                </tr>
                <tr>
                    <td><span class="badge badge-monthly">Månatlig</span></td>
                    <td>12 backuper</td>
                    <td>Senaste året</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>8. Krav för produktion</h2>
        <ul>
            <li>✓ mysqldump måste finnas installerat</li>
            <li>✓ PHP gzip-extension måste vara aktiverat</li>
            <li>✓ Skrivbehörighet i backups/ mappen</li>
            <li>✓ Tillräckligt diskutrymme (minst 5x databasstorlek)</li>
            <li>✓ Cron-jobb konfigurerat</li>
            <li>⚠ <strong>Rekommendation:</strong> Kopiera backuper till extern server eller molnlagring</li>
        </ul>
    </div>
</body>
</html>
