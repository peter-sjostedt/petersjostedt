<?php
/**
 * Admin - Sessioner
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
                    $message = t('admin.sessions.terminated');
                    $messageType = 'success';
                    Logger::write('SESSION_TERMINATED', Session::getUserId(), "Avslutade session ID: {$sessionId}");
                }
                break;

            case 'delete_user_sessions':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $affected = $db->delete('sessions', 'user_id = ?', [$userId]);
                $message = t('admin.sessions.terminated_count', ['count' => $affected]);
                $messageType = 'success';
                Logger::write('SESSION_TERMINATED', Session::getUserId(), "Avslutade alla sessioner för användare ID: {$userId}");
                break;

            case 'clean_expired':
                $affected = Session::cleanExpiredSessions();
                $message = t('admin.sessions.expired_cleaned', ['count' => $affected]);
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
    <title><?= t('admin.nav.sessions') ?> - <?= t('admin.title.prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="../assets/css/modal.css">
    <script src="../assets/js/modal.js"></script>
    <script src="js/admin.js" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <h1><?= t('admin.nav.sessions') ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat">
                <h3><?php echo $totalSessions; ?></h3>
                <p><?= t('admin.sessions.active') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo $uniqueUsers; ?></h3>
                <p><?= t('admin.sessions.unique_users') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo $uniqueIps; ?></h3>
                <p><?= t('admin.sessions.unique_ips') ?></p>
            </div>
            <div class="stat">
                <h3><?php echo $expiredCount; ?></h3>
                <p><?= t('admin.sessions.expired_to_clean') ?></p>
            </div>
        </div>

        <?php if ($expiredCount > 0): ?>
        <div class="toolbar">
            <form method="POST" action="" style="display:inline;">
                <?php echo Session::csrfField(); ?>
                <input type="hidden" name="action" value="clean_expired">
                <button type="submit" class="btn btn-secondary"><?= t('admin.sessions.clean_expired', ['count' => $expiredCount]) ?></button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2><?= t('admin.sessions.active') ?></h2>
            <table>
                <thead>
                    <tr>
                        <th><?= t('admin.logs.user') ?></th>
                        <th><?= t('field.role') ?></th>
                        <th><?= t('admin.logs.ip_address') ?></th>
                        <th><?= t('admin.sessions.expires') ?></th>
                        <th><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;"><?= t('admin.sessions.no_active') ?></td></tr>
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
                            <?php echo htmlspecialchars($session['name'] ?? $session['email'] ?? t('common.unknown')); ?>
                            <?php if ($isCurrentSession): ?>
                                <span class="badge badge-current"><?= t('admin.sessions.your_session') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?php echo $session['role'] ?? 'user'; ?>"><?php echo $session['role'] ?? 'user'; ?></span></td>
                        <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                        <td class="<?php echo $expiresSoon ? 'expires-soon' : ''; ?>">
                            <?php echo date('Y-m-d H:i', $expiresAt); ?>
                            <br><small style="color:#aaa;"><?= t('admin.sessions.hours_left', ['hours' => round($expiresIn / 3600, 1)]) ?></small>
                        </td>
                        <td>
                            <?php if (!$isCurrentSession): ?>
                            <form method="POST" action="" style="display:inline;" data-confirm="<?= t('admin.sessions.confirm_terminate') ?>">
                                <?php echo Session::csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small"><?= t('admin.sessions.terminate') ?></button>
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