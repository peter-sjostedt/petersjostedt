<?php
/**
 * Admin - Användarhantering
 * Med modal-system för skapa/redigera
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

$userModel = new User();
$orgModel = new Organization();
// Hämta alla organisationer utom system-organisationen
$allOrgs = $orgModel->findAll(null, true);
$organizations = array_values(array_filter($allOrgs, fn($o) => $o['org_type'] !== 'system'));

// AJAX API endpoint
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => t('error.invalid_request')]);
        exit;
    }

    if ($isAjax) {
        $action = $_POST['action'] ?? '';
        $result = ['success' => false, 'message' => ''];

        switch ($action) {
            case 'create':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $name = trim($_POST['name'] ?? '');
                $role = $_POST['role'] ?? 'user';
                $organizationId = !empty($_POST['organization_id']) ? $_POST['organization_id'] : null;

                if (empty($email) || empty($password) || empty($name)) {
                    $result = ['success' => false, 'message' => t('error.all_fields_required')];
                } elseif (strlen($password) < 8) {
                    $result = ['success' => false, 'message' => t('error.password_min_length')];
                } elseif ($role !== 'admin' && empty($organizationId)) {
                    $result = ['success' => false, 'message' => t('admin.users.requires_org')];
                } else {
                    $userId = $userModel->create($email, $password, $name, $role, $organizationId);
                    if ($userId) {
                        $result = ['success' => true, 'message' => t('admin.users.created')];
                        Logger::write(Logger::ACTION_CREATE, Session::getUserId(), "Skapade användare: {$email}");
                    } else {
                        $result = ['success' => false, 'message' => t('admin.users.create_failed')];
                    }
                }
                break;

            case 'update':
                $id = (int) ($_POST['id'] ?? 0);
                $role = $_POST['role'] ?? 'user';
                $organizationId = !empty($_POST['organization_id']) ? $_POST['organization_id'] : null;

                if ($role !== 'admin' && empty($organizationId)) {
                    $result = ['success' => false, 'message' => t('admin.users.requires_org')];
                } else {
                    $data = [
                        'email' => trim($_POST['email'] ?? ''),
                        'name' => trim($_POST['name'] ?? ''),
                        'role' => $role,
                        'organization_id' => $organizationId
                    ];

                    if ($userModel->update($id, $data)) {
                        $result = ['success' => true, 'message' => t('admin.users.updated')];
                        Logger::write(Logger::ACTION_UPDATE, Session::getUserId(), "Uppdaterade användare ID: {$id}");
                    } else {
                        $result = ['success' => false, 'message' => t('admin.users.update_failed')];
                    }
                }
                break;

            case 'update_password':
                $id = (int) ($_POST['id'] ?? 0);
                $password = $_POST['password'] ?? '';

                if (strlen($password) < 8) {
                    $result = ['success' => false, 'message' => t('error.password_min_length')];
                } elseif ($userModel->updatePassword($id, $password)) {
                    $result = ['success' => true, 'message' => t('admin.users.password_updated')];
                    Logger::write(Logger::ACTION_PASSWORD_CHANGE, Session::getUserId(), "Ändrade lösenord för användare ID: {$id}");
                } else {
                    $result = ['success' => false, 'message' => t('admin.users.password_update_failed')];
                }
                break;

            case 'delete':
                $id = (int) ($_POST['id'] ?? 0);

                if ($id === Session::getUserId()) {
                    $result = ['success' => false, 'message' => t('admin.users.cannot_delete_self')];
                } elseif ($userModel->delete($id)) {
                    $result = ['success' => true, 'message' => t('admin.users.deleted')];
                    Logger::write(Logger::ACTION_DELETE, Session::getUserId(), "Tog bort användare ID: {$id}");
                } else {
                    $result = ['success' => false, 'message' => t('admin.users.delete_failed')];
                }
                break;
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

// Hantera sortering och filtrering
$sortBy = $_GET['sort'] ?? 'name';
$sortOrder = $_GET['order'] ?? 'ASC';
$filterOrg = $_GET['org'] ?? null;

// Hämta organisationsinfo om filter är aktivt
$filterOrgData = null;
if ($filterOrg) {
    $filterOrgData = $orgModel->findById($filterOrg);
}

// Hämta användare (filtrerat om org är satt)
$users = $userModel->findAll(0, 0, $filterOrg, $sortBy, $sortOrder);
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.nav.users') ?> - <?= t('admin.title.prefix') ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="user-labels" content='<?= json_encode([
        'name' => t('field.name'),
        'email' => t('field.email'),
        'password' => t('field.password_min_8'),
        'new_password' => t('field.new_password_min_8'),
        'role' => t('field.role'),
        'organization' => t('field.organization'),
        'role_user' => t('admin.users.role_user'),
        'role_org_admin' => t('admin.users.role_org_admin'),
        'role_admin' => t('admin.users.role_admin'),
        'select' => t('common.select'),
        'create' => t('common.create'),
        'update' => t('common.update'),
        'cancel' => t('common.cancel'),
        'change_password' => t('admin.users.change_password'),
        'modal_create' => t('admin.users.create_new'),
        'modal_edit' => t('admin.users.edit'),
        'modal_delete' => t('admin.users.modal.delete.title'),
        'modal_delete_message' => t('admin.users.modal.delete.message'),
        'delete' => t('common.delete'),
        'requires_org' => t('admin.users.requires_org')
    ]) ?>'>
    <meta name="organizations-data" content='<?= json_encode(array_map(fn($o) => ['id' => $o['id'], 'name' => $o['name']], $organizations)) ?>'>
    <?php if ($filterOrg): ?>
    <meta name="filter-org" content="<?= htmlspecialchars($filterOrg) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= versioned('admin/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= versioned('assets/css/modal.css') ?>">
    <script src="<?= versioned('assets/js/modal.js') ?>"></script>
    <script src="<?= versioned('admin/js/admin.js') ?>" defer></script>
    <script src="<?= versioned('admin/js/users.js') ?>" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1><?= t('admin.nav.users') ?><?php if ($filterOrgData): ?> - <?= htmlspecialchars($filterOrgData['name']) ?><?php endif; ?></h1>
                <?php if ($filterOrg): ?>
                    <a href="users.php" style="font-size: 0.9rem;"><?= t('admin.users.show_all') ?></a>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-primary" id="createUserBtn"><?= t('admin.users.create_new') ?></button>
        </div>

        <!-- Lista användare -->
        <div class="card">
            <h2><?= t('admin.users.all_users', ['count' => count($users)]) ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>
                            <a href="?sort=name&order=<?= $sortBy === 'name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('field.name') ?>
                                <?php if ($sortBy === 'name'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=email&order=<?= $sortBy === 'email' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('field.email') ?>
                                <?php if ($sortBy === 'email'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=organization_name&order=<?= $sortBy === 'organization_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('field.organization') ?>
                                <?php if ($sortBy === 'organization_name'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=role&order=<?= $sortBy === 'role' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('field.role') ?>
                                <?php if ($sortBy === 'role'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($user['name']) ?></strong></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= $user['organization_name'] ? htmlspecialchars($user['organization_name']) : '-' ?></td>
                        <td><span class="badge badge-<?= $user['role'] ?>"><?= t('admin.users.role_' . $user['role']) ?></span></td>
                        <td class="actions">
                            <button type="button" class="btn btn-icon" data-user-edit='<?= json_encode($user) ?>' title="<?= t('common.edit') ?>">✏️</button>
                            <?php if ($user['id'] !== Session::getUserId()): ?>
                            <button type="button" class="btn btn-icon btn-icon-danger" data-user-delete='<?= json_encode(['id' => $user['id'], 'name' => $user['name']]) ?>' title="<?= t('common.delete') ?>">🗑️</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
