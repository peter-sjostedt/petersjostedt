<?php
/**
 * Admin - Loggar
 */

require_once __DIR__ . '/../public_html/includes/config.php';

Session::start();
Session::requireAdmin('login.php');

$logger = Logger::getInstance();

// Filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;

// Hämta loggar baserat på filter
switch ($filter) {
    case 'security':
        $logs = $logger->getSecurityAlerts(500);
        break;
    case 'logins':
        $logs = $logger->getByAction('LOGIN', 500);
        break;
    case 'errors':
        $logs = $logger->getByAction('ERROR', 500);
        break;
    default:
        $logs = $logger->getRecent(500);
}

// Sök i loggar
if ($search) {
    $logs = array_filter($logs, function($log) use ($search) {
        return stripos($log['action'], $search) !== false ||
               stripos($log['ip_address'], $search) !== false ||
               stripos($log['email'] ?? '', $search) !== false;
    });
    $logs = array_values($logs);
}

// Paginering
$total = count($logs);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$logs = array_slice($logs, $offset, $perPage);

// Statistik
$stats = $logger->getStats();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loggar - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #eee; min-height: 100vh; }
        .sidebar { position: fixed; left: 0; top: 0; width: 250px; height: 100vh; background: #16213e; padding: 2rem 0; }
        .sidebar h2 { color: #fff; padding: 0 1.5rem 1.5rem; border-bottom: 1px solid #0f3460; }
        .sidebar nav { margin-top: 1rem; }
        .sidebar a { display: block; padding: 1rem 1.5rem; color: #aaa; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 250px; padding: 2rem; }
        h1 { margin-bottom: 1.5rem; }
        .card { background: #16213e; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: #16213e; padding: 1rem; border-radius: 8px; text-align: center; }
        .stat h3 { font-size: 1.5rem; color: #e94560; }
        .stat p { color: #aaa; font-size: 0.875rem; }
        .filters { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        .filter-btn { padding: 0.5rem 1rem; background: #0f3460; color: #aaa; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .filter-btn:hover, .filter-btn.active { background: #e94560; color: #fff; }
        .search-box { display: flex; gap: 0.5rem; }
        .search-box input { padding: 0.5rem 1rem; border: 1px solid #0f3460; border-radius: 4px; background: #1a1a2e; color: #eee; }
        .search-box button { padding: 0.5rem 1rem; background: #e94560; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #0f3460; }
        th { color: #aaa; }
        .log-action { font-family: monospace; }
        .log-login { color: #28a745; }
        .log-failed { color: #dc3545; }
        .log-security { color: #ffc107; }
        .pagination { display: flex; gap: 0.5rem; margin-top: 1.5rem; justify-content: center; }
        .pagination a, .pagination span { padding: 0.5rem 1rem; background: #0f3460; color: #aaa; border-radius: 4px; text-decoration: none; }
        .pagination a:hover, .pagination .current { background: #e94560; color: #fff; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="users.php">Användare</a>
            <a href="settings.php">Inställningar</a>
            <a href="logs.php" class="active">Loggar</a>
            <a href="sessions.php">Sessioner</a>
            <a href="../public_html/">Visa sidan</a>
            <a href="logout.php">Logga ut</a>
        </nav>
    </aside>

    <main class="main">
        <h1>Loggar</h1>

        <div class="stats">
            <div class="stat">
                <h3><?php echo number_format($stats['total']); ?></h3>
                <p>Totalt</p>
            </div>
            <div class="stat">
                <h3><?php echo number_format($stats['last_24h']); ?></h3>
                <p>Senaste 24h</p>
            </div>
            <div class="stat">
                <h3><?php echo number_format($stats['failed_logins_24h']); ?></h3>
                <p>Misslyckade inloggningar</p>
            </div>
            <div class="stat">
                <h3><?php echo number_format($stats['unique_ips_24h']); ?></h3>
                <p>Unika IP:er (24h)</p>
            </div>
        </div>

        <div class="filters">
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">Alla</a>
            <a href="?filter=security" class="filter-btn <?php echo $filter === 'security' ? 'active' : ''; ?>">Säkerhet</a>
            <a href="?filter=logins" class="filter-btn <?php echo $filter === 'logins' ? 'active' : ''; ?>">Inloggningar</a>
            <a href="?filter=errors" class="filter-btn <?php echo $filter === 'errors' ? 'active' : ''; ?>">Fel</a>

            <form method="GET" class="search-box" style="margin-left:auto;">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="search" placeholder="Sök..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Sök</button>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Tid</th>
                        <th>Användare</th>
                        <th>Händelse</th>
                        <th>IP-adress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#aaa;">Inga loggar hittades</td></tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log):
                        $actionClass = '';
                        if (strpos($log['action'], 'LOGIN_FAILED') !== false) $actionClass = 'log-failed';
                        elseif (strpos($log['action'], 'LOGIN') !== false) $actionClass = 'log-login';
                        elseif (strpos($log['action'], 'SECURITY') !== false) $actionClass = 'log-security';
                    ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['name'] ?? $log['email'] ?? '-'); ?></td>
                        <td class="log-action <?php echo $actionClass; ?>"><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">Föregående</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Nästa</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
