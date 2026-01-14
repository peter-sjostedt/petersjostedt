<?php
/**
 * Hospitex Partner Portal - Dashboard
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

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

// Kräv inloggning
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// System admin redirectas till admin
if (Session::isSystemAdmin()) {
    header('Location: ../admin/index.php');
    exit;
}

// Kräv org_admin roll
if (!Session::isOrgAdmin()) {
    Session::flash('error', 'Du har inte behörighet att se denna sida');
    header('Location: login.php');
    exit;
}

$userData = Session::getUserData();
$organizationId = Session::getOrganizationId();

// Hämta organisationsdata
$orgModel = new Organization();
$organization = $orgModel->findById($organizationId);

if (!$organization) {
    Session::flash('error', 'Din organisation kunde inte hittas');
    Session::logout();
    header('Location: login.php');
    exit;
}

$pageTitle = 'Dashboard - ' . htmlspecialchars($organization['name']);
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="css/partner.css">
    <link rel="stylesheet" href="../assets/css/modal.css">
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <div class="header">
            <h1><?= t('admin.nav.dashboard') ?></h1>
            <div class="user-info">
                <?= t('admin.dashboard.logged_in_as') ?> <strong><?= htmlspecialchars($userData['name'] ?? $userData['email']) ?></strong>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3><?= $orgModel->getUnitsCount($organizationId) ?></h3>
                <p><?= t('partner.nav.units') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $orgModel->getArticlesCount($organizationId) ?></h3>
                <p><?= t('partner.nav.articles') ?></p>
            </div>
            <div class="stat-card">
                <h3><?= $orgModel->getRfidsCount($organizationId) ?></h3>
                <p><?= t('partner.nav.rfids') ?></p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p><?= t('partner.nav.events') ?></p>
            </div>
        </div>

        <div class="card">
            <h2><?= t('partner.welcome') ?></h2>
            <p><?= t('partner.welcome.text') ?></p>
        </div>
    </main>

    <script src="../assets/js/modal.js"></script>
</body>
</html>
