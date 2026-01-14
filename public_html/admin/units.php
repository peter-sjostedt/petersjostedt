<?php
/**
 * Enheter (Units) Administration
 * Visar och hanterar enheter för en specifik organisation
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();
Session::requireAdmin('login.php');

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

$db = Database::getInstance()->getPdo();
$lang = Language::getInstance();

// Hämta organisation ID från URL
$org_id = $_GET['org_id'] ?? null;
if (!$org_id) {
    header('Location: organizations.php');
    exit;
}

// Hämta organisation
$stmt = $db->prepare("SELECT * FROM organizations WHERE id = ?");
$stmt->execute([$org_id]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$org) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Organisation hittades inte'];
    header('Location: organizations.php');
    exit;
}

// Hantera POST-requests (CRUD operations)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => t('error.invalid_request')];
        header("Location: units.php?org_id=" . urlencode($org_id));
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $password = $_POST['password'] ?? '';
                $api_key = trim($_POST['api_key'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name)) {
                    throw new Exception(t('admin.units.message.error'));
                }

                if (empty($password) || strlen($password) < 8) {
                    throw new Exception('Lösenord måste vara minst 8 tecken');
                }

                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Generera API-nyckel om inte angiven
                if (empty($api_key)) {
                    $api_key = bin2hex(random_bytes(32));
                }

                $stmt = $db->prepare("
                    INSERT INTO units (organization_id, name, api_key, password, is_active)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$org_id, $name, $api_key, $password_hash, $is_active]);

                $_SESSION['flash'] = ['type' => 'success', 'message' => t('admin.units.message.created')];
                header("Location: units.php?org_id=" . urlencode($org_id));
                exit;

            case 'update':
                $id = (int) $_POST['id'];
                $name = trim($_POST['name'] ?? '');
                $password = $_POST['password'] ?? '';
                $api_key = trim($_POST['api_key'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name)) {
                    throw new Exception(t('admin.units.message.error'));
                }

                // Om lösenord angivet, uppdatera det
                if (!empty($password)) {
                    if (strlen($password) < 8) {
                        throw new Exception('Lösenord måste vara minst 8 tecken');
                    }
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("
                        UPDATE units
                        SET name = ?, api_key = ?, password = ?, is_active = ?
                        WHERE id = ? AND organization_id = ?
                    ");
                    $stmt->execute([$name, $api_key, $password_hash, $is_active, $id, $org_id]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE units
                        SET name = ?, api_key = ?, is_active = ?
                        WHERE id = ? AND organization_id = ?
                    ");
                    $stmt->execute([$name, $api_key, $is_active, $id, $org_id]);
                }

                $_SESSION['flash'] = ['type' => 'success', 'message' => t('admin.units.message.updated')];
                header("Location: units.php?org_id=" . urlencode($org_id));
                exit;

            case 'delete':
                $id = (int) $_POST['id'];

                $stmt = $db->prepare("DELETE FROM units WHERE id = ? AND organization_id = ?");
                $stmt->execute([$id, $org_id]);

                $_SESSION['flash'] = ['type' => 'success', 'message' => t('admin.units.message.deleted')];
                header("Location: units.php?org_id=" . urlencode($org_id));
                exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        header("Location: units.php?org_id=" . urlencode($org_id));
        exit;
    }
}

// Hämta alla enheter för organisationen
$stmt = $db->prepare("
    SELECT * FROM units
    WHERE organization_id = ?
    ORDER BY name ASC
");
$stmt->execute([$org_id]);
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = str_replace('{org}', htmlspecialchars($org['name']), t('admin.units.title'));
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= t('admin.title.prefix') ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="unit-labels" content='<?= json_encode([
        'name' => t('admin.units.form.name'),
        'name_help' => t('admin.units.form.name_help'),
        'password' => t('admin.units.form.password'),
        'password_help' => t('admin.units.form.password_help'),
        'password_leave_blank' => t('admin.units.form.password_leave_blank'),
        'api_key' => t('admin.units.form.api_key'),
        'api_key_help' => t('admin.units.form.api_key_help'),
        'generate_api_key' => t('admin.units.form.generate_api_key'),
        'copy_api_key' => t('admin.units.form.copy_api_key'),
        'generate_new_api_key' => t('admin.units.form.generate_new_api_key'),
        'is_active' => t('admin.units.form.is_active'),
        'modal_create' => t('admin.units.modal.create.title'),
        'modal_edit' => t('admin.units.modal.edit.title'),
        'create' => t('admin.units.create'),
        'update' => t('common.save'),
        'cancel' => t('common.cancel'),
    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="../assets/css/modal.css">
    <script src="../assets/js/modal.js"></script>
    <script src="js/units.js" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1><?= $pageTitle ?></h1>
            <button type="button" class="btn btn-primary" id="createUnitBtn">
                <?= t('admin.units.create') ?>
            </button>
        </div>

        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash flash-<?= $_SESSION['flash']['type'] ?>">
                <?= htmlspecialchars($_SESSION['flash']['message']) ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <?php if (empty($units)): ?>
            <div class="card">
                <p style="text-align: center; color: #aaa; padding: 2rem;">
                    <?= t('admin.units.no_units') ?>
                </p>
            </div>
        <?php else: ?>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th><?= t('admin.units.table.name') ?></th>
                            <th><?= t('admin.units.table.api_key') ?></th>
                            <th><?= t('admin.units.table.is_active') ?></th>
                            <th><?= t('admin.units.table.last_login') ?></th>
                            <th><?= t('admin.units.table.created') ?></th>
                            <th style="text-align: right;"><?= t('admin.units.table.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $unit): ?>
                        <tr>
                            <td><?= htmlspecialchars($unit['name']) ?></td>
                            <td><code style="font-size: 0.85em;"><?= htmlspecialchars(substr($unit['api_key'], 0, 16)) ?>...</code></td>
                            <td><?= $unit['is_active'] ? '✅' : '❌' ?></td>
                            <td><?= $unit['last_login_at'] ? date('Y-m-d H:i', strtotime($unit['last_login_at'])) : '-' ?></td>
                            <td><?= date('Y-m-d', strtotime($unit['created_at'])) ?></td>
                            <td style="text-align: right;">
                                <button type="button"
                                        class="btn btn-small"
                                        data-unit-edit='<?= json_encode($unit, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    <?= t('admin.units.action.edit') ?>
                                </button>
                                <button type="button"
                                        class="btn btn-small btn-danger"
                                        data-unit-delete='<?= json_encode(['id' => $unit['id'], 'name' => $unit['name']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    <?= t('admin.units.action.delete') ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
