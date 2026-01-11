<?php
/**
 * Admin - Databasbackuper
 *
 * Hantera och återställ databasbackuper.
 */

require_once __DIR__ . '/../public_html/includes/config.php';

Session::start();
Session::requireAdmin('login.php');

$backup = Backup::getInstance();
$logger = Logger::getInstance();

$message = null;
$error = null;

// Hantera POST-requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = t('error.csrf_invalid');
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $type = $_POST['type'] ?? 'daily';
                if (!in_array($type, ['daily', 'weekly', 'monthly'])) {
                    $error = t('admin.backup.error.invalid_type');
                } else {
                    $result = $backup->createBackup($type);
                    if ($result['success']) {
                        $message = t('admin.backup.success.created', [
                            'filename' => $result['filename'],
                            'size' => $result['size_human']
                        ]);
                    } else {
                        $error = t('admin.backup.error.create_failed', ['error' => $result['error']]);
                    }
                }
                break;

            case 'restore':
                $backupFile = $_POST['backup_file'] ?? '';
                if (empty($backupFile)) {
                    $error = t('admin.backup.error.no_file_selected');
                } else {
                    // Säkerhetskontroll
                    $realPath = realpath($backup->getInstance()->listBackups('all')[0]['path'] ?? '');
                    $backupDir = dirname($realPath);
                    $requestedPath = $backupDir . '/' . basename($backupFile);

                    if (file_exists($requestedPath)) {
                        $result = $backup->restoreBackup($requestedPath);
                        if ($result['success']) {
                            $message = t('admin.backup.success.restored', ['filename' => basename($backupFile)]);
                        } else {
                            $error = t('admin.backup.error.restore_failed', ['error' => $result['error']]);
                        }
                    } else {
                        $error = t('admin.backup.error.file_not_found');
                    }
                }
                break;

            case 'delete':
                $backupFile = $_POST['backup_file'] ?? '';
                if (empty($backupFile)) {
                    $error = t('admin.backup.error.no_file_selected');
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
                    } else {
                        $error = t('admin.backup.error.delete_failed');
                    }
                }
                break;

            case 'rotate':
                $rotateStats = $backup->rotateBackups();
                $message = t('admin.backup.success.rotated', [
                    'promoted' => $rotateStats['promoted'],
                    'deleted' => $rotateStats['deleted']
                ]);
                break;
        }
    }
}

// Hämta backuper och statistik
$backups = $backup->listBackups('all');
$stats = $backup->getStats();

$pageTitle = t('admin.backup.title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-header">
    <div>
        <h1><?= t('admin.backup.heading') ?></h1>
        <p><?= t('admin.backup.description') ?></p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Statistik -->
    <div class="card">
        <h2><?= t('admin.backup.stats.heading') ?></h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label"><?= t('admin.backup.stats.total') ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $stats['daily'] ?></div>
                <div class="stat-label"><?= t('admin.backup.stats.daily') ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $stats['weekly'] ?></div>
                <div class="stat-label"><?= t('admin.backup.stats.weekly') ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $stats['monthly'] ?></div>
                <div class="stat-label"><?= t('admin.backup.stats.monthly') ?></div>
            </div>
        </div>

        <?php if (isset($stats['total_size_human'])): ?>
            <div class="info-box" style="margin-top: 1rem;">
                <p><strong><?= t('admin.backup.stats.total_size') ?>:</strong> <?= htmlspecialchars($stats['total_size_human']) ?></p>
                <p><strong><?= t('admin.backup.stats.oldest') ?>:</strong> <?= htmlspecialchars($stats['oldest']) ?></p>
                <p><strong><?= t('admin.backup.stats.newest') ?>:</strong> <?= htmlspecialchars($stats['newest']) ?></p>
            </div>
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
</div>

<!-- Befintliga backuper -->
<div class="card">
    <h2><?= t('admin.backup.list.heading') ?></h2>

    <?php if (empty($backups)): ?>
        <p><?= t('admin.backup.list.empty') ?></p>
    <?php else: ?>
        <div class="table-responsive">
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
        </div>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
