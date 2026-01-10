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
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.nav.logs') ?> - <?= t('admin.title.prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <h1><?= t('admin.nav.logs') ?></h1>

        <div class="stats">
            <div class="stat">
                <h3><?php echo number_format($stats['total']); ?></h3>
                <p><?= t('admin.logs.total') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo number_format($stats['last_24h']); ?></h3>
                <p><?= t('admin.logs.last_24h') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo number_format($stats['failed_logins_24h']); ?></h3>
                <p><?= t('admin.logs.failed_logins') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo number_format($stats['unique_ips_24h']); ?></h3>
                <p><?= t('admin.logs.unique_ips_24h') ?></p>
            </div>
        </div>

        <div class="filters">
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>"><?= t('filter.all') ?></a>
            <a href="?filter=security" class="filter-btn <?php echo $filter === 'security' ? 'active' : ''; ?>"><?= t('filter.security') ?></a>
            <a href="?filter=logins" class="filter-btn <?php echo $filter === 'logins' ? 'active' : ''; ?>"><?= t('filter.logins') ?></a>
            <a href="?filter=errors" class="filter-btn <?php echo $filter === 'errors' ? 'active' : ''; ?>"><?= t('filter.errors') ?></a>

            <form method="GET" class="search-box" style="margin-left:auto;">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="search" placeholder="<?= t('admin.logs.search_placeholder') ?>" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><?= t('common.search') ?></button>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th><?= t('admin.logs.time') ?></th>
                        <th><?= t('admin.logs.user') ?></th>
                        <th><?= t('admin.logs.event') ?></th>
                        <th><?= t('admin.logs.ip_address') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#aaa;"><?= t('admin.logs.no_results') ?></td></tr>
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
                    <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>"><?= t('pagination.previous') ?></a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>"><?= t('pagination.next') ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>