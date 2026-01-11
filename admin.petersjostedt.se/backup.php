<?php
/**
 * Admin - Databasbackuper
 *
 * Hantera och återställ databasbackuper.
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

$backup = Backup::getInstance();
$logger = Logger::getInstance();

$message = '';
$messageType = '';

// Hantera POST-requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrf($_POST['csrf_token'] ?? '')) {
        $message = t('error.csrf_invalid');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $type = $_POST['type'] ?? 'daily';
                if (!in_array($type, ['daily', 'weekly', 'monthly'])) {
                    $message = t('admin.backup.error.invalid_type');
                    $messageType = 'error';
                } else {
                    $result = $backup->createBackup($type);
                    if ($result['success']) {
                        $message = t('admin.backup.success.created', [
                            'filename' => $result['filename'],
                            'size' => $result['size_human']
                        ]);
                        $messageType = 'success';
                    } else {
                        $message = t('admin.backup.error.create_failed', ['error' => $result['error']]);
                        $messageType = 'error';
                    }
                }
                break;

            case 'restore':
                $backupFile = $_POST['backup_file'] ?? '';
                if (empty($backupFile)) {
                    $message = t('admin.backup.error.no_file_selected');
                    $messageType = 'error';
                } else {
                    // Säkerhetskontroll
                    $realPath = realpath($backup->getInstance()->listBackups('all')[0]['path'] ?? '');
                    $backupDir = dirname($realPath);
                    $requestedPath = $backupDir . '/' . basename($backupFile);

                    if (file_exists($requestedPath)) {
                        $result = $backup->restoreBackup($requestedPath);
                        if ($result['success']) {
                            $message = t('admin.backup.success.restored', ['filename' => basename($backupFile)]);
                            $messageType = 'success';
                        } else {
                            $message = t('admin.backup.error.restore_failed', ['error' => $result['error']]);
                            $messageType = 'error';
                        }
                    } else {
                        $message = t('admin.backup.error.file_not_found');
                        $messageType = 'error';
                    }
                }
                break;

            case 'delete':
                $backupFile = $_POST['backup_file'] ?? '';
                if (empty($backupFile)) {
                    $message = t('admin.backup.error.no_file_selected');
                    $messageType = 'error';
                } else {
                    $allBackups = $backup->listBackups('all');
                    $toDelete = null;

                    foreach ($allBackups as $b) {
                        if ($b['name'] === basename($backupFile)) {
                            $toDelete = $b['path'];
                            break;
                        }
                    }

                    if ($toDelete && $backup->deleteBackup($toDelete)) {
                        $message = t('admin.backup.success.deleted', ['filename' => basename($backupFile)]);
                        $messageType = 'success';
                    } else {
                        $message = t('admin.backup.error.delete_failed');
                        $messageType = 'error';
                    }
                }
                break;

            case 'rotate':
                $rotateStats = $backup->rotateBackups();
                $message = t('admin.backup.success.rotated', [
                    'promoted' => $rotateStats['promoted'],
                    'deleted' => $rotateStats['deleted']
                ]);
                $messageType = 'success';
                break;
        }
    }
}

// Hämta backuper och statistik
$backups = $backup->listBackups('all');
$stats = $backup->getStats();
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.backup.title') ?> - <?= t('admin.title.prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="js/admin.js" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="header">
            <h1><?= t('admin.backup.heading') ?></h1>
            <p><?= t('admin.backup.description') ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="card">
            <h2><?= t('admin.backup.stats.heading') ?></h2>
            <p><strong><?= t('admin.backup.stats.total') ?>:</strong> <?= $stats['total'] ?></p>
            <p><strong><?= t('admin.backup.stats.daily') ?>:</strong> <?= $stats['daily'] ?></p>
            <p><strong><?= t('admin.backup.stats.weekly') ?>:</strong> <?= $stats['weekly'] ?></p>
            <p><strong><?= t('admin.backup.stats.monthly') ?>:</strong> <?= $stats['monthly'] ?></p>

            <?php if (isset($stats['total_size_human'])): ?>
                <hr>
                <p><strong><?= t('admin.backup.stats.total_size') ?>:</strong> <?= htmlspecialchars($stats['total_size_human']) ?></p>
                <p><strong><?= t('admin.backup.stats.oldest') ?>:</strong> <?= htmlspecialchars($stats['oldest']) ?></p>
                <p><strong><?= t('admin.backup.stats.newest') ?>:</strong> <?= htmlspecialchars($stats['newest']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Skapa ny backup -->
        <div class="card">
            <h2><?= t('admin.backup.create.heading') ?></h2>
        <form method="POST" onsubmit="return confirm('<?= t('admin.backup.create.confirm') ?>');">
            <?= Session::csrfField() ?>
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label><?= t('admin.backup.create.type') ?></label>
                <select name="type" required>
                    <option value="daily"><?= t('admin.backup.type.daily') ?></option>
                    <option value="weekly"><?= t('admin.backup.type.weekly') ?></option>
                    <option value="monthly"><?= t('admin.backup.type.monthly') ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <?= t('admin.backup.create.button') ?>
            </button>
        </form>

        <hr style="margin: 1.5rem 0;">

        <form method="POST" onsubmit="return confirm('<?= t('admin.backup.rotate.confirm') ?>');">
            <?= Session::csrfField() ?>
            <input type="hidden" name="action" value="rotate">
            <button type="submit" class="btn btn-secondary">
                <?= t('admin.backup.rotate.button') ?>
            </button>
        </form>
        </div>

        <!-- Befintliga backuper -->
        <div class="card">
    <h2><?= t('admin.backup.list.heading') ?></h2>

    <?php if (empty($backups)): ?>
        <p><?= t('admin.backup.list.empty') ?></p>
    <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><?= t('admin.backup.list.filename') ?></th>
                        <th><?= t('admin.backup.list.type') ?></th>
                        <th><?= t('admin.backup.list.size') ?></th>
                        <th><?= t('admin.backup.list.created') ?></th>
                        <th><?= t('admin.backup.list.age') ?></th>
                        <th><?= t('admin.backup.list.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $b): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($b['name']) ?></code></td>
                            <td>
                                <span class="badge badge-<?= $b['type'] ?>">
                                    <?= t('admin.backup.type.' . $b['type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($b['size_human']) ?></td>
                            <td><?= htmlspecialchars($b['modified_human']) ?></td>
                            <td><?= t('admin.backup.list.days_ago', ['days' => $b['age_days']]) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('<?= t('admin.backup.restore.confirm') ?>');">
                                        <?= Session::csrfField() ?>
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($b['name']) ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <?= t('admin.backup.restore.button') ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('<?= t('admin.backup.delete.confirm') ?>');">
                                        <?= Session::csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($b['name']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <?= t('admin.backup.delete.button') ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    <?php endif; ?>
        </div>

        <!-- Instruktioner -->
        <div class="card">
    <h2><?= t('admin.backup.instructions.heading') ?></h2>

    <h3><?= t('admin.backup.instructions.cron.heading') ?></h3>
    <p><?= t('admin.backup.instructions.cron.description') ?></p>
    <pre>0 3 * * * cd <?= dirname(__DIR__) ?> && php cron/backup-database.php</pre>

    <h3 style="margin-top: 2rem;"><?= t('admin.backup.instructions.types.heading') ?></h3>
    <ul>
        <li><strong><?= t('admin.backup.type.daily') ?>:</strong> <?= t('admin.backup.instructions.types.daily') ?></li>
        <li><strong><?= t('admin.backup.type.weekly') ?>:</strong> <?= t('admin.backup.instructions.types.weekly') ?></li>
        <li><strong><?= t('admin.backup.type.monthly') ?>:</strong> <?= t('admin.backup.instructions.types.monthly') ?></li>
    </ul>

    <h3 style="margin-top: 2rem;"><?= t('admin.backup.instructions.restore.heading') ?></h3>
    <p><?= t('admin.backup.instructions.restore.warning') ?></p>
        </div>
    </main>
</body>
</html>
