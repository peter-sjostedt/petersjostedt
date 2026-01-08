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
        $message = 'Ogiltig förfrågan.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'delete':
                $sessionId = (int) ($_POST['session_id'] ?? 0);
                $affected = $db->delete('sessions', 'id = ?', [$sessionId]);
                if ($affected) {
                    $message = 'Session avslutad!';
                    $messageType = 'success';
                    Logger::write('SESSION_TERMINATED', Session::getUserId(), "Avslutade session ID: {$sessionId}");
                }
                break;

            case 'delete_user_sessions':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $affected = $db->delete('sessions', 'user_id = ?', [$userId]);
                $message = "{$affected} sessioner avslutade!";
                $messageType = 'success';
                Logger::write('SESSION_TERMINATED', Session::getUserId(), "Avslutade alla sessioner för användare ID: {$userId}");
                break;

            case 'clean_expired':
                $affected = Session::cleanExpiredSessions();
                $message = "{$affected} utgångna sessioner rensade!";
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
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessioner - Admin</title>
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
        .card h2 { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #0f3460; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: #16213e; padding: 1rem; border-radius: 8px; text-align: center; }
        .stat h3 { font-size: 1.5rem; color: #e94560; }
        .stat p { color: #aaa; font-size: 0.875rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #0f3460; }
        th { color: #aaa; }
        .btn { padding: 0.5rem 1rem; background: #e94560; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #c73e54; }
        .btn-secondary { background: #0f3460; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 0.4rem 0.75rem; font-size: 0.875rem; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; }
        .message.success { background: #28a74533; color: #28a745; }
        .message.error { background: #dc354533; color: #dc3545; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-admin { background: #e94560; }
        .badge-user { background: #0f3460; }
        .badge-current { background: #28a745; }
        .toolbar { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .expires-soon { color: #ffc107; }
        .current-session { background: #28a74522; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="users.php">Användare</a>
            <a href="settings.php">Inställningar</a>
            <a href="logs.php">Loggar</a>
            <a href="sessions.php" class="active">Sessioner</a>
            <a href="../public_html/">Visa sidan</a>
            <a href="logout.php">Logga ut</a>
        </nav>
    </aside>

    <main class="main">
        <h1>Sessioner</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat">
                <h3><?php echo $totalSessions; ?></h3>
                <p>Aktiva sessioner</p>
            </div>
            <div class="stat">
                <h3><?php echo $uniqueUsers; ?></h3>
                <p>Unika användare</p>
            </div>
            <div class="stat">
                <h3><?php echo $uniqueIps; ?></h3>
                <p>Unika IP-adresser</p>
            </div>
            <div class="stat">
                <h3><?php echo $expiredCount; ?></h3>
                <p>Utgångna (att rensa)</p>
            </div>
        </div>

        <?php if ($expiredCount > 0): ?>
        <div class="toolbar">
            <form method="POST" action="" style="display:inline;">
                <?php echo Session::csrfField(); ?>
                <input type="hidden" name="action" value="clean_expired">
                <button type="submit" class="btn btn-secondary">Rensa <?php echo $expiredCount; ?> utgångna sessioner</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Aktiva sessioner</h2>
            <table>
                <thead>
                    <tr>
                        <th>Användare</th>
                        <th>Roll</th>
                        <th>IP-adress</th>
                        <th>Utgår</th>
                        <th>Åtgärder</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;">Inga aktiva sessioner</td></tr>
                    <?php else: ?>
                    <?php
                    $currentToken = $_SESSION['_db_token'] ?? '';
                    foreach ($sessions as $session):
                        $isCurrentSession = ($session['token'] === $currentToken);
                        $expiresAt = strtotime($session['expires_at']);
                        $expiresIn = $expiresAt - time();
                        $expiresSoon = $expiresIn < 3600; // Mindre än 1 timme
                    ?>
                    <tr class="<?php echo $isCurrentSession ? 'current-session' : ''; ?>">
                        <td>
                            <?php echo htmlspecialchars($session['name'] ?? $session['email'] ?? 'Okänd'); ?>
                            <?php if ($isCurrentSession): ?>
                                <span class="badge badge-current">Din session</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?php echo $session['role'] ?? 'user'; ?>"><?php echo $session['role'] ?? 'user'; ?></span></td>
                        <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                        <td class="<?php echo $expiresSoon ? 'expires-soon' : ''; ?>">
                            <?php echo date('Y-m-d H:i', $expiresAt); ?>
                            <br><small style="color:#aaa;"><?php echo round($expiresIn / 3600, 1); ?>h kvar</small>
                        </td>
                        <td>
                            <?php if (!$isCurrentSession): ?>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Avsluta denna session?');">
                                <?php echo Session::csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">Avsluta</button>
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
