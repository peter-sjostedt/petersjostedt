<?php
/**
 * Test - Loggningssystem
 *
 * Testar förbättrad loggning med filstöd, nivåer och rotation.
 */

require_once __DIR__ . '/includes/config.php';

Session::start();
Session::requireAdmin('login.php');

$logger = Logger::getInstance();
$testResults = [];

// Test 1: Olika loggningsnivåer
$testResults['levels'] = [
    'debug' => $logger->debug('TEST_DEBUG', 1, 'Detta är en debug-logg'),
    'info' => $logger->info('TEST_INFO', 1, 'Detta är en info-logg'),
    'warning' => $logger->warning('TEST_WARNING', 1, 'Detta är en varningslogg'),
    'error' => $logger->error('TEST_ERROR', 1, 'Detta är en fellogg'),
    'security' => $logger->security('TEST_SECURITY', 1, 'Detta är en säkerhetslogg')
];

// Test 2: Läs loggfiler
$logFiles = $logger->getLogFiles(true);

// Test 3: Läs innehåll från dagens app-logg
$todayLog = 'app-' . date('Y-m-d') . '.log';
$appLogContent = $logger->readLogFile($todayLog, 10, true);

// Test 4: Statistik
$stats = $logger->getStats();

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Loggningssystem</title>
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
        .log-line {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-left: 3px solid #0f3460;
            margin: 0.25rem 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .level-DEBUG { border-left-color: #6c757d; }
        .level-INFO { border-left-color: #0dcaf0; }
        .level-WARNING { border-left-color: #ffc107; }
        .level-ERROR { border-left-color: #dc3545; }
        .level-SECURITY { border-left-color: #e94560; }
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
        .back-link {
            display: inline-block;
            color: #0f3460;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-link:hover {
            color: #e94560;
        }
    </style>
</head>
<body>
    <a href="../admin.petersjostedt.se/" class="back-link">← Tillbaka till admin</a>

    <h1>Test - Förbättrat loggningssystem</h1>

    <div class="card">
        <h2>1. Test av loggningsnivåer</h2>
        <p>Testar olika loggningsnivåer med både databas- och filloggning:</p>
        <table>
            <tr>
                <th>Nivå</th>
                <th>Status</th>
            </tr>
            <?php foreach ($testResults['levels'] as $level => $result): ?>
            <tr>
                <td><?= strtoupper($level) ?></td>
                <td class="<?= $result ? 'success' : 'error' ?>">
                    <?= $result ? '✓ OK' : '✗ Misslyckades' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>2. Loggstatistik</h2>
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-value"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Totalt antal loggar</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= number_format($stats['last_24h']) ?></div>
                <div class="stat-label">Senaste 24h</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= number_format($stats['failed_logins_24h']) ?></div>
                <div class="stat-label">Misslyckade inloggningar</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= number_format($stats['unique_users_24h']) ?></div>
                <div class="stat-label">Unika användare 24h</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= number_format($stats['unique_ips_24h']) ?></div>
                <div class="stat-label">Unika IP-adresser 24h</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>3. Loggfiler på disk</h2>
        <?php if (empty($logFiles)): ?>
            <p>Inga loggfiler hittades.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Filnamn</th>
                    <th>Storlek</th>
                    <th>Senast ändrad</th>
                </tr>
                <?php foreach ($logFiles as $file): ?>
                <tr>
                    <td><?= htmlspecialchars($file['name']) ?></td>
                    <td><?= htmlspecialchars($file['size_human']) ?></td>
                    <td><?= htmlspecialchars($file['modified_human']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>4. Senaste 10 loggraderna från dagens app-logg</h2>
        <?php if ($appLogContent === false): ?>
            <p>Loggfilen kunde inte läsas eller finns inte än.</p>
        <?php elseif (empty($appLogContent)): ?>
            <p>Loggfilen är tom.</p>
        <?php else: ?>
            <?php foreach ($appLogContent as $line): ?>
                <?php
                // Extrahera nivå för färgkodning
                preg_match('/\[([A-Z]+)\]/', $line, $matches);
                $level = $matches[1] ?? 'INFO';
                ?>
                <div class="log-line level-<?= $level ?>"><?= htmlspecialchars($line) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>5. Funktioner tillgängliga</h2>
        <ul>
            <li><strong>Filbaserad loggning:</strong> Alla loggar skrivs till filer i logs/ mappen</li>
            <li><strong>Loggningsnivåer:</strong> DEBUG, INFO, WARNING, ERROR, SECURITY</li>
            <li><strong>Automatisk rotation:</strong> Komprimera efter 7 dagar, radera efter 90 dagar</li>
            <li><strong>Separata loggfiler:</strong> app-*.log, error-*.log, security-*.log, debug-*.log</li>
            <li><strong>Gzip-komprimering:</strong> Sparar diskutrymme</li>
            <li><strong>Redundans:</strong> Loggar både till databas OCH fil</li>
            <li><strong>Fallback:</strong> Om databas är nere, loggas endast till fil</li>
        </ul>
    </div>

    <div class="card">
        <h2>6. Cron-jobb för loggrotation</h2>
        <p>Lägg till i crontab för automatisk loggrotation:</p>
        <pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px;">0 2 * * * cd <?= dirname(__DIR__) ?> && php cron/rotate-logs.php</pre>
        <p>Detta kör loggrotation kl 02:00 varje natt.</p>
    </div>
</body>
</html>
