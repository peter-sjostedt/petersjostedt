<?php
/**
 * Admin Login
 */

require_once __DIR__ . '/../public_html/includes/config.php';

Session::start();

// Om redan inloggad, redirect till dashboard
if (Session::isLoggedIn()) {
    $userData = Session::getUserData();
    if (isset($userData['role']) && $userData['role'] === 'admin') {
        header('Location: index.php');
        exit;
    }
}

$error = '';
$logger = Logger::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifiera CSRF
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = t('error.invalid_request');
    }
    // Kontrollera rate limiting
    elseif ($logger->isIpBlocked($_SERVER['REMOTE_ADDR'], 5, 15)) {
        $error = t('error.too_many_attempts');
        Logger::write(Logger::ACTION_SECURITY_ALERT, null, 'IP blockerad: ' . $_SERVER['REMOTE_ADDR']);
    }
    else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user = $userModel->authenticate($email, $password);

        if ($user && $user['role'] === 'admin') {
            // Inloggning lyckades
            Session::login($user['id'], $user);
            Logger::write(Logger::ACTION_LOGIN, $user['id'], 'Admin login');
            header('Location: index.php');
            exit;
        } else {
            $error = t('error.invalid_credentials_or_no_admin');
            Logger::write(Logger::ACTION_LOGIN_FAILED, null, 'Email: ' . $email);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.login') ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1><?= t('admin.login') ?></h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($flash = Session::getFlash('error')): ?>
            <div class="error"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo Session::csrfField(); ?>

            <div class="form-group">
                <label for="email"><?= t('user.email') ?></label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password"><?= t('user.password') ?></label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn"><?= t('user.login') ?></button>
        </form>

        <a href="../public_html/" class="back-link"><?= t('admin.back_to_site') ?></a>
    </div>
</body>
</html>