<?php
/**
 * Admin Dashboard
 * Kräver inloggning med admin-roll
 */

require_once __DIR__ . '/../public_html/includes/config.php';

// Starta session och kräv admin
Session::start();
Session::requireAdmin('/login.php');

$userId = Session::getUserId();
$userData = Session::getUserData();

// Hämta statistik
$logger = Logger::getInstance();
$stats = $logger->getStats();

$userModel = new User();
$userCount = $userModel->count();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #eee;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #16213e;
            padding: 2rem 0;
        }
        .sidebar h2 {
            color: #fff;
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid #0f3460;
        }
        .sidebar nav { margin-top: 1rem; }
        .sidebar a {
            display: block;
            padding: 1rem 1.5rem;
            color: #aaa;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #0f3460;
            color: #fff;
        }
        .main {
            margin-left: 250px;
            padding: 2rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header h1 { font-size: 1.8rem; }
        .user-info {
            background: #16213e;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #16213e;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #e94560;
        }
        .stat-card h3 {
            font-size: 2rem;
            color: #e94560;
        }
        .stat-card p {
            color: #aaa;
            margin-top: 0.5rem;
        }
        .card {
            background: #16213e;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .card h2 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #0f3460;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #e94560;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #c73e54; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #0f3460;
        }
        th { color: #aaa; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <nav>
            <a href="index.php" class="active">Dashboard</a>
            <a href="users.php">Användare</a>
            <a href="settings.php">Inställningar</a>
            <a href="logs.php">Loggar</a>
            <a href="sessions.php">Sessioner</a>
            <a href="../public_html/">Visa sidan</a>
            <a href="logout.php">Logga ut</a>
        </nav>
    </aside>

    <main class="main">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                Inloggad som: <strong><?php echo htmlspecialchars($userData['name'] ?? 'Admin'); ?></strong>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $userCount; ?></h3>
                <p>Användare</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['last_24h']; ?></h3>
                <p>Händelser (24h)</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['unique_users_24h']; ?></h3>
                <p>Aktiva användare (24h)</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['failed_logins_24h']; ?></h3>
                <p>Misslyckade inloggningar (24h)</p>
            </div>
        </div>

        <div class="card">
            <h2>Senaste aktivitet</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tid</th>
                        <th>Användare</th>
                        <th>Händelse</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recentLogs = $logger->getRecent(10);
                    foreach ($recentLogs as $log):
                    ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['name'] ?? $log['email'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
