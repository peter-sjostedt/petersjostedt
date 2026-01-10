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
    'site_name' => ['label' => t('settings.site_name'), 'type' => 'text', 'default' => SITE_NAME],
    'site_description' => ['label' => t('settings.description'), 'type' => 'textarea', 'default' => ''],
    'site_email' => ['label' => t('settings.contact_email'), 'type' => 'email', 'default' => ''],
    'maintenance_mode' => ['label' => t('settings.maintenance_mode'), 'type' => 'checkbox', 'default' => false],
    'allow_registration' => ['label' => t('settings.allow_registration'), 'type' => 'checkbox', 'default' => true],
    'items_per_page' => ['label' => t('settings.items_per_page'), 'type' => 'number', 'default' => 20],
    'session_lifetime' => ['label' => t('settings.session_lifetime'), 'type' => 'number', 'default' => 86400],
    'max_login_attempts' => ['label' => t('settings.max_login_attempts'), 'type' => 'number', 'default' => 5],
    'lockout_time' => ['label' => t('settings.lockout_time'), 'type' => 'number', 'default' => 15],
];

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
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
            $message = t('settings.saved');
            $messageType = 'success';
            Logger::write(Logger::ACTION_SETTINGS_CHANGE, Session::getUserId(), 'Uppdaterade systeminställningar');
        } else {
            $message = t('settings.save_failed');
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
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.settings') ?> - <?= t('admin.title_prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <h1><?= t('admin.settings') ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2><?= t('settings.system_settings') ?></h2>
            <form method="POST" action="">
                <?php echo Session::csrfField(); ?>

                <p class="section-title"><?= t('settings.general') ?></p>

                <div class="form-group">
                    <label for="site_name"><?= t('settings.site_name') ?></label>
                    <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($currentSettings['site_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="site_description"><?= t('settings.description') ?></label>
                    <textarea id="site_description" name="site_description"><?php echo htmlspecialchars($currentSettings['site_description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="site_email"><?= t('settings.contact_email') ?></label>
                    <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($currentSettings['site_email']); ?>">
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $currentSettings['maintenance_mode'] ? 'checked' : ''; ?>>
                        <label for="maintenance_mode"><?= t('settings.maintenance_mode_desc') ?></label>
                    </div>
                </div>

                <p class="section-title"><?= t('settings.users') ?></p>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="allow_registration" name="allow_registration" <?php echo $currentSettings['allow_registration'] ? 'checked' : ''; ?>>
                        <label for="allow_registration"><?= t('settings.allow_registration_desc') ?></label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="items_per_page"><?= t('settings.items_per_page') ?></label>
                    <input type="number" id="items_per_page" name="items_per_page" value="<?php echo (int) $currentSettings['items_per_page']; ?>" min="5" max="100">
                </div>

                <p class="section-title"><?= t('settings.security') ?></p>

                <div class="form-group">
                    <label for="session_lifetime"><?= t('settings.session_lifetime') ?></label>
                    <input type="number" id="session_lifetime" name="session_lifetime" value="<?php echo (int) $currentSettings['session_lifetime']; ?>" min="300">
                    <p class="help-text"><?= t('settings.session_lifetime_help') ?></p>
                </div>

                <div class="form-group">
                    <label for="max_login_attempts"><?= t('settings.max_login_attempts') ?></label>
                    <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?php echo (int) $currentSettings['max_login_attempts']; ?>" min="1" max="20">
                    <p class="help-text"><?= t('settings.max_login_attempts_help') ?></p>
                </div>

                <div class="form-group">
                    <label for="lockout_time"><?= t('settings.lockout_time') ?></label>
                    <input type="number" id="lockout_time" name="lockout_time" value="<?php echo (int) $currentSettings['lockout_time']; ?>" min="1" max="60">
                    <p class="help-text"><?= t('settings.lockout_time_help') ?></p>
                </div>

                <button type="submit" class="btn"><?= t('settings.save') ?></button>
            </form>
        </div>

        <div class="card">
            <h2><?= t('settings.system_info') ?></h2>
            <table style="width:100%;">
                <tr><td style="color:#aaa;padding:0.5rem 0;"><?= t('settings.php_version') ?></td><td><?php echo phpversion(); ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;"><?= t('settings.server') ?></td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? t('unknown'); ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;"><?= t('settings.environment') ?></td><td><?php echo ENVIRONMENT; ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;"><?= t('settings.database') ?></td><td><?php echo DB_NAME; ?></td></tr>
                <tr><td style="color:#aaa;padding:0.5rem 0;"><?= t('settings.timezone') ?></td><td><?php echo date_default_timezone_get(); ?></td></tr>
            </table>
        </div>
    </main>
</body>
</html>