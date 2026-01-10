<?php
/**
 * Admin Sidebar
 * Inkludera i alla admin-sidor
 */

$lang = Language::getInstance();

// Hantera språkbyte
if (isset($_GET['set_lang'])) {
    $lang->setLanguage($_GET['set_lang']);
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['set_lang']);
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <h2><?= t('admin.title.panel') ?></h2>

    <div class="lang-switcher">
        <select onchange="window.location.href='?set_lang='+this.value">
            <?php foreach ($lang->getLanguages() as $code => $info): ?>
                <option value="<?= $code ?>" <?= $code === $lang->getLanguage() ? 'selected' : '' ?>>
                    <?= $info['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <nav>
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"><?= t('admin.nav.dashboard') ?></a>
        <a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>"><?= t('admin.nav.users') ?></a>
        <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>"><?= t('admin.nav.settings') ?></a>
        <a href="logs.php" class="<?= $currentPage === 'logs.php' ? 'active' : '' ?>"><?= t('admin.nav.logs') ?></a>
        <a href="sessions.php" class="<?= $currentPage === 'sessions.php' ? 'active' : '' ?>"><?= t('admin.nav.sessions') ?></a>
        <a href="../public_html/"><?= t('admin.nav.view_site') ?></a>
        <a href="logout.php"><?= t('common.logout') ?></a>
    </nav>
</aside>