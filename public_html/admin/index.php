<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();
Session::requireAdmin('login.php');

// Hantera språkbyte
if (isset($_GET['set_lang'])) {
    Language::getInstance()->setLanguage($_GET['set_lang']);
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['set_lang']);
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

$userId = Session::getUserId();
$userData = Session::getUserData();

$logger = Logger::getInstance();
$stats = $logger->getStats();

$userModel = new User();
$userCount = $userModel->count();
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.nav.dashboard') ?> - <?= t('admin.title.prefix') ?></title>
    <link rel="stylesheet" href="<?= versioned('admin/css/admin.css') ?>">
    <script src="<?= versioned('admin/js/admin.js') ?>" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="header">
            <h1><?= t('admin.nav.dashboard') ?></h1>
            <div class="user-info">
                <?= t('admin.dashboard.logged_in_as') ?> <strong><?= htmlspecialchars($userData['name'] ?? t('admin.dashboard.admin')) ?></strong>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3><?= $userCount ?></h3>
                <p><?= t('admin.nav.users') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['last_24h'] ?></h3>
                <p><?= t('admin.dashboard.events_24h') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['unique_users_24h'] ?></h3>
                <p><?= t('admin.dashboard.active_users_24h') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['failed_logins_24h'] ?></h3>
                <p><?= t('admin.dashboard.failed_logins_24h') ?></p>
            </div>
        </div>

        <div class="card">
            <h2><?= t('admin.dashboard.recent_activity') ?></h2>
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
                    <?php
                    $recentLogs = $logger->getRecent(10);
                    foreach ($recentLogs as $log):
                    ?>
                    <tr>
                        <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                        <td><?= htmlspecialchars($log['name'] ?? $log['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>