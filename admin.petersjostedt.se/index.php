<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../public_html/includes/config.php';

Session::start();
Session::requireAdmin('/login.php');

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
    <title><?= t('admin.dashboard') ?> - <?= t('admin.title_prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="header">
            <h1><?= t('admin.dashboard') ?></h1>
            <div class="user-info">
                <?= t('admin.logged_in_as') ?> <strong><?= htmlspecialchars($userData['name'] ?? t('admin.admin')) ?></strong>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3><?= $userCount ?></h3>
                <p><?= t('admin.users') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['last_24h'] ?></h3>
                <p><?= t('admin.events_24h') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['unique_users_24h'] ?></h3>
                <p><?= t('admin.active_users_24h') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['failed_logins_24h'] ?></h3>
                <p><?= t('admin.failed_logins_24h') ?></p>
            </div>
        </div>

        <div class="card">
            <h2><?= t('admin.recent_activity') ?></h2>
            <table>
                <thead>
                    <tr>
                        <th><?= t('admin.time') ?></th>
                        <th><?= t('admin.user') ?></th>
                        <th><?= t('admin.event') ?></th>
                        <th><?= t('admin.ip') ?></th>
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