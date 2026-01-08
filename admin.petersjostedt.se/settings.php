<?php
/**
 * Admin - Inställningar
 */

require_once __DIR__ . '/../public_html/includes/config.php';

Session::start();
Session::requireAdmin('login.php');

$settings = Settings::getInstance();
$message = '';
$messageType = '';

// Fördefinierade inställningar
$settingFields = [
    'site_name' => ['label' => 'Sidans namn', 'type' => 'text', 'default' => SITE_NAME],
    'site_description' => ['label' => 'Beskrivning', 'type' => 'textarea', 'default' => ''],
    'site_email' => ['label' => 'Kontakt-epost', 'type' => 'email', 'default' => ''],
    'maintenance_mode' => ['label' => 'Underhållsläge', 'type' => 'checkbox', 'default' => false],
    'allow_registration' => ['label' => 'Tillåt registrering', 'type' => 'checkbox', 'default' => true],
    'items_per_page' => ['label' => 'Poster per sida', 'type' => 'number', 'default' => 20],
    'session_lifetime' => ['label' => 'Session-livstid (sekunder)', 'type' => 'number', 'default' => 86400],
    'max_login_attempts' => ['label' => 'Max inloggningsförsök', 'type' => 'number', 'default' => 5],
    'lockout_time' => ['label' => 'Spärrtid vid för många försök (minuter)', 'type' => 'number', 'default' => 15],
];

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Ogiltig förfrågan.';
        $messageType = 'error';
    } else {
        $updatedSettings = [];

        foreach ($settingFields as $key => $field) {
            if ($field['type'] === 'checkbox') {
                $updatedSettings[$key] = isset($_POST[$key]) ? true : false;
            } else {
                $updatedSettings[$key] = $_POST[$key] ?? $field['default'];
            }
        }

        if ($settings->setMany($updatedSettings)) {
            $message = 'Inställningar sparade!';
            $messageType = 'success';
            Logger::write(Logger::ACTION_SETTINGS_CHANGE, Session::getUserId(), 'Uppdaterade systeminställningar');
        } else {
            $message = 'Kunde inte spara inställningar.';
            $messageType = 'error';
        }
    }
}

// Hämta aktuella värden
$currentSettings = [];
foreach ($settingFields as $key => $field) {
    $currentSettings[$key] = $settings->get($key, $field['default']);
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inställningar - Admin</title>
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
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; color: #aaa; }
        input[type="text"], input[type="email"], input[type="number"], textarea {
            width: 100%; max-width: 500px; padding: 0.75rem; border: 1px solid #0f3460;
            border-radius: 4px; background: #1a1a2e; color: #eee;
        }
        textarea { min-height: 100px; resize: vertical; }
        input:focus, textarea:focus { outline: none; border-color: #e94560; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-group input { width: auto; }
        .checkbox-group label { margin: 0; color: #eee; }
        .btn { padding: 0.75rem 1.5rem; background: #e94560; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #c73e54; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; }
        .message.success { background: #28a74533; color: #28a745; }
        .message.error { background: #dc354533; color: #dc3545; }
        .section-title { color: #e94560; margin: 2rem 0 1rem; font-size: 1.1rem; }
        .help-text { font-size: 0.875rem; color: #666; margin-top: 0.25rem; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="users.php">Användare</a>
            <a href="settings.php" class="active">Inställningar</a>
            <a href="logs.php">Loggar</a>
            <a href="sessions.php">Sessioner</a>
            <a href="../public_html/">Visa sidan</a>
            <a href="logout.php">Logga ut</a>
        </nav>
    </aside>

    <main class="main">
        <h1>Inställningar</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Systeminställningar</h2>
            <form method="POST" action="">
                <?php echo Session::csrfField(); ?>

                <p class="section-title">Allmänt</p>

                <div class="form-group">
                    <label for="site_name">Sidans namn</label>
                    <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($currentSettings['site_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="site_description">Beskrivning</label>
                    <textarea id="site_description" name="site_description"><?php echo htmlspecialchars($currentSettings['site_description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="site_email">Kontakt-epost</label>
                    <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($currentSettings['site_email']); ?>">
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $currentSettings['maintenance_mode'] ? 'checked' : ''; ?>>
                        <label for="maintenance_mode">Underhållsläge (stänger sidan för besökare)</label>
                    </div>
                </div>

                <p class="section-title">Användare</p>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="allow_registration" name="allow_registration" <?php echo $currentSettings['allow_registration'] ? 'checked' : ''; ?>>
                        <label for="allow_registration">Tillåt registrering av nya användare</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="items_per_page">Poster per sida</label>
                    <input type="number" id="items_per_page" name="items_per_page" value="<?php echo (int) $currentSettings['items_per_page']; ?>" min="5" max="100">
                </div>

                <p class="section-title">Säkerhet</p>

                <div class="form-group">
                    <label for="session_lifetime">Session-livstid (sekunder)</label>
                    <input type="number" id="session_lifetime" name="session_lifetime" value="<?php echo (int) $currentSettings['session_lifetime']; ?>" min="300">
                    <p class="help-text">86400 = 24 timmar, 3600 = 1 timme</p>
                </div>

                <div class="form-group">
                    <label for="max_login_attempts">Max inloggningsförsök</label>
                    <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?php echo (int) $currentSettings['max_login_attempts']; ?>" min="1" max="20">
                    <p class="help-text">Antal misslyckade försök innan IP blockeras</p>
                </div>

                <div class="form-group">
                    <label for="lockout_time">Spärrtid (minuter)</label>
                    <input type="number" id="lockout_time" name="lockout_time" value="<?php echo (int) $currentSettings['lockout_time']; ?>" min="1" max="60">
                    <p class="help-text">Hur länge en IP är blockerad efter för många försök</p>
                </div>

                <button type="submit" class="btn">Spara inställningar</button>
            </form>
        </div>

        <div class="card">
            <h2>Systeminformation</h2>
            <table style="width:100%;">
                <tr><td style="color:#aaa;padding:0.5rem 0;">PHP-version</td><td><?php echo phpversion(); ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;">Server</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Okänd'; ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;">Miljö</td><td><?php echo ENVIRONMENT; ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;">Databas</td><td><?php echo DB_NAME; ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;">Tidzon</td><td><?php echo date_default_timezone_get(); ?></td></tr>
            </table>
        </div>
    </main>
</body>
</html>
