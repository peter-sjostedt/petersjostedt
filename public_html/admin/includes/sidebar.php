<?php
/**
 * Admin Sidebar
 * Inkludera i alla admin-sidor
 * Visar olika menyer beroende på användarens roll
 */

$lang = Language::getInstance();
$currentPage = basename($_SERVER['PHP_SELF']);

// Hämta användarens roll från session
$isSystemAdmin = Session::isSystemAdmin();
$isOrgAdmin = Session::isOrgAdmin();
$orgId = Session::getOrganizationId();
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
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">🏠 <?= t('admin.nav.dashboard') ?></a>

        <?php if ($isSystemAdmin): ?>
            <!-- System Admin: Full åtkomst -->
            <div class="menu-group">
                <div class="menu-group-header">📋 <?= t('admin.nav.group.content') ?></div>
                <div class="menu-group-items">
                    <a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">👥 <?= t('admin.nav.users') ?></a>
                    <a href="organizations.php" class="<?= $currentPage === 'organizations.php' ? 'active' : '' ?>">🏢 <?= t('admin.organizations.nav') ?></a>
                    <a href="files.php" class="<?= $currentPage === 'files.php' ? 'active' : '' ?>">📁 <?= t('admin.files.nav') ?></a>
                </div>
            </div>

            <div class="menu-group">
                <div class="menu-group-header">⚙️ <?= t('admin.nav.group.system') ?></div>
                <div class="menu-group-items">
                    <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">⚙️ <?= t('admin.nav.settings') ?></a>
                    <a href="logs.php" class="<?= $currentPage === 'logs.php' ? 'active' : '' ?>">📝 <?= t('admin.nav.logs') ?></a>
                    <a href="sessions.php" class="<?= $currentPage === 'sessions.php' ? 'active' : '' ?>">🔑 <?= t('admin.nav.sessions') ?></a>
                </div>
            </div>

            <div class="menu-group">
                <div class="menu-group-header">💾 <?= t('admin.nav.group.data') ?></div>
                <div class="menu-group-items">
                    <a href="backup.php" class="<?= $currentPage === 'backup.php' ? 'active' : '' ?>">💾 <?= t('admin.nav.backup') ?></a>
                    <a href="migrations.php" class="<?= $currentPage === 'migrations.php' ? 'active' : '' ?>">🔄 <?= t('admin.nav.migrations') ?></a>
                </div>
            </div>

        <?php elseif ($isOrgAdmin && $orgId): ?>
            <!-- Organization Admin: Endast sin egen organisations data -->
            <div class="menu-group">
                <div class="menu-group-header">📦 RFID-System</div>
                <div class="menu-group-items">
                    <a href="articles.php?org_id=<?= urlencode($orgId) ?>" class="<?= $currentPage === 'articles.php' ? 'active' : '' ?>">📦 Artiklar</a>
                    <a href="rfids.php?org_id=<?= urlencode($orgId) ?>" class="<?= $currentPage === 'rfids.php' ? 'active' : '' ?>">🏷️ RFID-taggar</a>
                    <a href="units.php?org_id=<?= urlencode($orgId) ?>" class="<?= $currentPage === 'units.php' ? 'active' : '' ?>">📡 Enheter</a>
                    <a href="events.php?org_id=<?= urlencode($orgId) ?>" class="<?= $currentPage === 'events.php' ? 'active' : '' ?>">📊 Händelser</a>
                </div>
            </div>

            <div class="menu-group">
                <div class="menu-group-header">⚙️ Inställningar</div>
                <div class="menu-group-items">
                    <a href="org-settings.php?org_id=<?= urlencode($orgId) ?>" class="<?= $currentPage === 'org-settings.php' ? 'active' : '' ?>">🏢 Organisation</a>
                    <a href="org-users.php?org_id=<?= urlencode($orgId) ?>" class="<?= $currentPage === 'org-users.php' ? 'active' : '' ?>">👥 Användare</a>
                </div>
            </div>
        <?php endif; ?>

        <a href="https://petersjostedt.se" target="_blank">🌐 <?= t('admin.nav.view_site') ?></a>
        <a href="logout.php">🚪 <?= t('common.logout') ?></a>
    </nav>
    <script src="js/sidebar.js"></script>
</aside>