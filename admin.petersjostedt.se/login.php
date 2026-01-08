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
        $error = 'Ogiltig förfrågan. Försök igen.';
    }
    // Kontrollera rate limiting
    elseif ($logger->isIpBlocked($_SERVER['REMOTE_ADDR'], 5, 15)) {
        $error = 'För många misslyckade försök. Vänta 15 minuter.';
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
            $error = 'Felaktiga inloggningsuppgifter eller saknar admin-behörighet.';
            Logger::write(Logger::ACTION_LOGIN_FAILED, null, 'Email: ' . $email);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #eee;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: #16213e;
            padding: 2.5rem;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #e94560;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #aaa;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #0f3460;
            border-radius: 4px;
            background: #1a1a2e;
            color: #eee;
            font-size: 1rem;
        }
        input:focus {
            outline: none;
            border-color: #e94560;
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #e94560;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #c73e54; }
        .error {
            background: #e9456033;
            color: #e94560;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #aaa;
            text-decoration: none;
        }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Admin Login</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($flash = Session::getFlash('error')): ?>
            <div class="error"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo Session::csrfField(); ?>

            <div class="form-group">
                <label for="email">E-post</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Lösenord</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Logga in</button>
        </form>

        <a href="../public_html/" class="back-link">Tillbaka till sidan</a>
    </div>
</body>
</html>
