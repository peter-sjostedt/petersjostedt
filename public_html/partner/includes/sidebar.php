<?php
/**
 * Partner Portal Sidebar
 * Samma konstruktion som admin-sidebar, eget innehåll
 */

$langInstance = Language::getInstance();
$currentPage = basename($_SERVER['PHP_SELF']);
$orgId = Session::getOrganizationId();

// Hämta organisationsdata om inte redan satt
if (!isset($organization) && $orgId) {
    $orgModel = new Organization();
    $organization = $orgModel->findById($orgId);
}
?>
<aside class="sidebar">
    <h2><?= htmlspecialchars($organization['name'] ?? 'Partner Portal') ?></h2>

    <div class="lang-switcher">
        <select>
            <?php foreach ($langInstance->getLanguages() as $code => $info): ?>
                <option value="<?= $code ?>" <?= $code === $langInstance->getLanguage() ? 'selected' : '' ?>>
                    <?= $info['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <nav>
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">🏠 <?= t('admin.nav.dashboard') ?></a>

        <div class="menu-group">
            <div class="menu-group-header">📦 <?= t('partner.nav.rfid_system') ?></div>
            <div class="menu-group-items">
                <a href="articles.php" class="<?= $currentPage === 'articles.php' ? 'active' : '' ?>">📦 <?= t('partner.nav.articles') ?></a>
                <a href="rfids.php" class="<?= $currentPage === 'rfids.php' ? 'active' : '' ?>">🏷️ <?= t('partner.nav.rfids') ?></a>
                <a href="units.php" class="<?= $currentPage === 'units.php' ? 'active' : '' ?>">📡 <?= t('partner.nav.units') ?></a>
            </div>
        </div>

        <div class="menu-group">
            <div class="menu-group-header">📋 <?= t('partner.nav.events_group') ?></div>
            <div class="menu-group-items">
                <a href="templates.php" class="<?= $currentPage === 'templates.php' ? 'active' : '' ?>">📝 <?= t('partner.nav.templates') ?></a>
                <a href="shipments.php" class="<?= $currentPage === 'shipments.php' ? 'active' : '' ?>">📦 <?= t('partner.nav.shipments') ?></a>
            </div>
        </div>

        <div class="menu-group">
            <div class="menu-group-header">⚙️ <?= t('partner.nav.settings') ?></div>
            <div class="menu-group-items">
                <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">🏢 <?= t('partner.nav.organization') ?></a>
                <a href="relations.php" class="<?= $currentPage === 'relations.php' ? 'active' : '' ?>">🤝 <?= t('partner.nav.relations') ?></a>
                <a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">👥 <?= t('partner.nav.users') ?></a>
            </div>
        </div>

        <a href="logout.php">🚪 <?= t('common.logout') ?></a>
    </nav>
</aside>
