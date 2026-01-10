<?php
/**
 * Admin Sidebar
 * Inkludera i alla admin-sidor
 */

$lang = Language::getInstance();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <h2><?= t('admin.title.panel') ?></h2>

    <div class="lang-switcher">
        <select>
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