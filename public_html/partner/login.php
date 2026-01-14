<?php
/**
 * Partner Login
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

// Om redan inloggad som org_admin, redirect till dashboard
if (Session::isLoggedIn()) {
    if (Session::isOrgAdmin()) {
        header('Location: index.php');
        exit;
    } elseif (Session::isSystemAdmin()) {
        header('Location: ../admin/index.php');
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

        if ($user && $user['role'] === 'org_admin') {
            // Inloggning lyckades
            Session::login($user['id'], $user);
            Logger::write(Logger::ACTION_LOGIN, $user['id'], 'Partner login');
            header('Location: index.php');
            exit;
        } elseif ($user && $user['role'] === 'admin') {
            // System admin, redirect till admin
            Session::login($user['id'], $user);
            Logger::write(Logger::ACTION_LOGIN, $user['id'], 'Admin login via partner portal');
            header('Location: ../admin/index.php');
            exit;
        } else {
            $error = t('error.invalid_credentials');
            Logger::write(Logger::ACTION_LOGIN_FAILED, null, 'Partner login failed - Email: ' . $email);
        }
    }
}

$pageTitle = t('partner.login.title') ?? 'Partner Login';
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Hospitex</title>
    <link rel="stylesheet" href="../assets/css/modal.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .container {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
        }

        h1 {
            text-align: center;
            color: #667eea;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .error {
            background: #fee;
            color: #c00;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }

        .info strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Hospitex</h1>
        <p class="subtitle">Partner Portal - RFID Tracking System</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($flash = Session::getFlash('error')): ?>
            <div class="error"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= Session::csrfField() ?>

            <div class="form-group">
                <label for="email"><?= t('field.email') ?></label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password"><?= t('field.password') ?></label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn"><?= t('common.login') ?></button>
        </form>

        <div class="info">
            <strong>Partner-information:</strong><br>
            Efter inloggning får du tillgång till:
            <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                <li>Artikelhantering</li>
                <li>RFID-taggning</li>
                <li>Enhetsövervakning</li>
                <li>Händelselogg</li>
            </ul>
        </div>
    </div>

    <script src="../assets/js/modal.js"></script>
</body>
</html>
