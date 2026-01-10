<?php
/**
 * Admin - Sessioner
 */

require_once __DIR__ . '/../public_html/includes/config.php';

Session::start();
Session::requireAdmin('login.php');

$db = Database::getInstance();
$message = '';
$messageType = '';

// Hantera åtgärder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'delete':
                $sessionId = (int) ($_POST['session_id'] ?? 0);
                $affected = $db->delete('sessions', 'id = ?', [$sessionId]);
                if ($affected) {
                    $message = t('session.terminated');
                    $messageType = 'success';
                    Logger::write('SESSION_TERMINATED', Session::getUserId(), "Avslutade session ID: {$sessionId}");
                }
                break;

            case 'delete_user_sessions':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $affected = $db->delete('sessions', 'user_id = ?', [$userId]);
                $message = t('session.terminated_count', ['count' => $affected]);
                $messageType = 'success';
                Logger::write('SESSION_TERMINATED', Session::getUserId(), "Avslutade alla sessioner för användare ID: {$userId}");
                break;

            case 'clean_expired':
                $affected = Session::cleanExpiredSessions();
                $message = t('session.expired_cleaned', ['count' => $affected]);
                $messageType = 'success';
                Logger::write('SESSION_CLEANUP', Session::getUserId(), "Rensade {$affected} utgångna sessioner");
                break;
        }
    }
}

// Hämta alla aktiva sessioner
$sessions = $db->fetchAll(
    "SELECT s.*, u.email, u.name, u.role
     FROM sessions s
     LEFT JOIN users u ON s.user_id = u.id
     WHERE s.expires_at > NOW()
     ORDER BY s.expires_at DESC"
);

// Räkna utgångna sessioner
$expiredCount = (int) $db->fetchColumn("SELECT COUNT(*) FROM sessions WHERE expires_at < NOW()");

// Statistik
$totalSessions = count($sessions);
$uniqueUsers = count(array_unique(array_column($sessions, 'user_id')));
$uniqueIps = count(array_unique(array_column($sessions, 'ip_address')));
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.sessions') ?> - <?= t('admin.title_prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <h1><?= t('admin.sessions') ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat">
                <h3><?php echo $totalSessions; ?></h3>
                <p><?= t('session.active_sessions') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo $uniqueUsers; ?></h3>
                <p><?= t('session.unique_users') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo $uniqueIps; ?></h3>
                <p><?= t('session.unique_ips') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo $expiredCount; ?></h3>
                <p><?= t('session.expired_to_clean') ?></p>
            </div>
        </div>

        <?php if ($expiredCount > 0): ?>
        <div class="toolbar">
            <form method="POST" action="" style="display:inline;">
                <?php echo Session::csrfField(); ?>
                <input type="hidden" name="action" value="clean_expired">
                <button type="submit" class="btn btn-secondary"><?= t('session.clean_expired', ['count' => $expiredCount]) ?></button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2><?= t('session.active_sessions') ?></h2>
            <table>
                <thead>
                    <tr>
                        <th><?= t('admin.user') ?></th>
                        <th><?= t('user.role') ?></th>
                        <th><?= t('admin.ip_address') ?></th>
                        <th><?= t('session.expires') ?></th>
                        <th><?= t('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;"><?= t('session.no_active') ?></td></tr>
                    <?php else: ?>
                    <?php
                    $currentToken = $_SESSION['_db_token'] ?? '';
                    foreach ($sessions as $session):
                        $isCurrentSession = ($session['token'] === $currentToken);
                        $expiresAt = strtotime($session['expires_at']);
                        $expiresIn = $expiresAt - time();
                        $expiresSoon = $expiresIn < 3600;
                    ?>
                    <tr class="<?php echo $isCurrentSession ? 'current-session' : ''; ?>">
                        <td>
                            <?php echo htmlspecialchars($session['name'] ?? $session['email'] ?? t('unknown')); ?>
                            <?php if ($isCurrentSession): ?>
                                <span class="badge badge-current"><?= t('session.your_session') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?php echo $session['role'] ?? 'user'; ?>"><?php echo $session['role'] ?? 'user'; ?></span></td>
                        <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                        <td class="<?php echo $expiresSoon ? 'expires-soon' : ''; ?>">
                            <?php echo date('Y-m-d H:i', $expiresAt); ?>
                            <br><small style="color:#aaa;"><?= t('session.hours_left', ['hours' => round($expiresIn / 3600, 1)]) ?></small>
                        </td>
                        <td>
                            <?php if (!$isCurrentSession): ?>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('<?= t('session.confirm_terminate') ?>');">
                                <?php echo Session::csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small"><?= t('session.terminate') ?></button>
                            </form>
                            <?php else: ?>
                            <span style="color:#aaa;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>