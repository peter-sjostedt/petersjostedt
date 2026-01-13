<?php
/**
 * Admin - Databasmigrations
 *
 * Webb-gränssnitt för att köra och hantera migrations.
 */

require_once __DIR__ . '/../public_html/includes/config.php';

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

$migration = Migration::getInstance();
$message = '';
$messageType = '';

// Hantera POST-requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.csrf_invalid');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'migrate':
                $result = $migration->migrate();

                if (isset($result['message'])) {
                    $message = $result['message'];
                    $messageType = 'info';
                } elseif ($result['success']) {
                    $count = count($result['executed']);
                    $message = t('admin.migrations.success.migrated', ['count' => $count]);
                    $messageType = 'success';
                    Logger::getInstance()->info('MIGRATIONS_RUN', Session::getUserId(), "$count migrations kördes");
                } else {
                    $errorCount = count($result['errors']);
                    $message = t('admin.migrations.error.failed', ['count' => $errorCount]);
                    $messageType = 'error';
                    Logger::getInstance()->error('MIGRATIONS_FAILED', Session::getUserId(), json_encode($result['errors']));
                }
                break;

            case 'rollback':
                $result = $migration->rollback();

                if (isset($result['message'])) {
                    $message = $result['message'];
                    $messageType = 'info';
                } elseif ($result['success']) {
                    $count = count($result['rolled_back']);
                    $message = t('admin.migrations.success.rolled_back', ['count' => $count]);
                    $messageType = 'success';
                    Logger::getInstance()->warning('MIGRATIONS_ROLLBACK', Session::getUserId(), "$count migrations återställdes");
                } else {
                    $message = t('admin.migrations.error.rollback_failed');
                    $messageType = 'error';
                    Logger::getInstance()->error('MIGRATIONS_ROLLBACK_FAILED', Session::getUserId(), json_encode($result['errors']));
                }
                break;
        }
    }
}

// Hämta status
$status = $migration->status();
$pendingCount = count(array_filter($status, fn($s) => !$s['executed']));
$executedCount = count(array_filter($status, fn($s) => $s['executed']));
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.migrations.title') ?> - <?= t('admin.title.prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="js/admin.js" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="header">
            <h1><?= t('admin.migrations.heading') ?></h1>
            <p><?= t('admin.migrations.description') ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Sammanfattning -->
        <div class="card">
            <h2><?= t('admin.migrations.summary.heading') ?></h2>
            <p><strong><?= t('admin.migrations.summary.total') ?>:</strong> <?= count($status) ?></p>
            <p><strong><?= t('admin.migrations.summary.executed') ?>:</strong> <?= $executedCount ?></p>
            <p><strong><?= t('admin.migrations.summary.pending') ?>:</strong> <?= $pendingCount ?></p>
        </div>

        <!-- Åtgärder -->
        <?php if ($pendingCount > 0): ?>
        <div class="card">
            <h2><?= t('admin.migrations.actions.heading') ?></h2>
            <p><?= t('admin.migrations.actions.pending_info', ['count' => $pendingCount]) ?></p>

            <form method="POST" onsubmit="return confirm('<?= t('admin.migrations.actions.migrate_confirm') ?>');">
                <?= Session::csrfField() ?>
                <input type="hidden" name="action" value="migrate">
                <button type="submit" class="btn btn-primary">
                    <?= t('admin.migrations.actions.run_button') ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($executedCount > 0): ?>
        <div class="card">
            <h2><?= t('admin.migrations.rollback.heading') ?></h2>
            <p><?= t('admin.migrations.rollback.warning') ?></p>

            <form method="POST" onsubmit="return confirm('<?= t('admin.migrations.rollback.confirm') ?>');">
                <?= Session::csrfField() ?>
                <input type="hidden" name="action" value="rollback">
                <button type="submit" class="btn btn-danger">
                    <?= t('admin.migrations.rollback.button') ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Lista över migrations -->
        <div class="card">
            <h2><?= t('admin.migrations.list.heading') ?></h2>

            <?php if (empty($status)): ?>
                <p><?= t('admin.migrations.list.empty') ?></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?= t('admin.migrations.list.status') ?></th>
                            <th><?= t('admin.migrations.list.migration') ?></th>
                            <th><?= t('admin.migrations.list.batch') ?></th>
                            <th><?= t('admin.migrations.list.executed_at') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['executed']): ?>
                                        <span class="badge badge-success">✓ <?= t('admin.migrations.list.executed') ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">○ <?= t('admin.migrations.list.pending') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= htmlspecialchars($item['migration']) ?></code></td>
                                <td>
                                    <?php if ($item['batch']): ?>
                                        <?= $item['batch'] ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['executed_at']): ?>
                                        <?= htmlspecialchars($item['executed_at']) ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Information -->
        <div class="card">
            <h2><?= t('admin.migrations.info.heading') ?></h2>

            <h3><?= t('admin.migrations.info.what.heading') ?></h3>
            <p><?= t('admin.migrations.info.what.description') ?></p>

            <h3 style="margin-top: 2rem;"><?= t('admin.migrations.info.when.heading') ?></h3>
            <ul>
                <li><?= t('admin.migrations.info.when.first_setup') ?></li>
                <li><?= t('admin.migrations.info.when.after_update') ?></li>
                <li><?= t('admin.migrations.info.when.new_features') ?></li>
            </ul>

            <h3 style="margin-top: 2rem;"><?= t('admin.migrations.info.cli.heading') ?></h3>
            <p><?= t('admin.migrations.info.cli.description') ?></p>
            <pre>php database/migrate.php</pre>
        </div>
    </main>
</body>
</html>
