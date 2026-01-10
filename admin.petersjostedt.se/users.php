<?php
/**
 * Admin - Användarhantering
 */

require_once __DIR__ . '/../public_html/includes/config.php';

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
$message = '';
$messageType = '';

// Hantera åtgärder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $name = trim($_POST['name'] ?? '');
                $role = $_POST['role'] ?? 'user';

                if (empty($email) || empty($password) || empty($name)) {
                    $message = t('error.all_fields_required');
                    $messageType = 'error';
                } elseif (strlen($password) < 8) {
                    $message = t('error.password_min_length');
                    $messageType = 'error';
                } else {
                    $userId = $userModel->create($email, $password, $name, $role);
                    if ($userId) {
                        $message = t('admin.users.created');
                        $messageType = 'success';
                        Logger::write(Logger::ACTION_CREATE, Session::getUserId(), "Skapade användare: {$email}");
                    } else {
                        $message = t('admin.users.create_failed');
                        $messageType = 'error';
                    }
                }
                break;

            case 'update':
                $id = (int) ($_POST['id'] ?? 0);
                $data = [
                    'email' => trim($_POST['email'] ?? ''),
                    'name' => trim($_POST['name'] ?? ''),
                    'role' => $_POST['role'] ?? 'user'
                ];

                if ($userModel->update($id, $data)) {
                    $message = t('admin.users.updated');
                    $messageType = 'success';
                    Logger::write(Logger::ACTION_UPDATE, Session::getUserId(), "Uppdaterade användare ID: {$id}");
                } else {
                    $message = t('admin.users.update_failed');
                    $messageType = 'error';
                }
                break;

            case 'update_password':
                $id = (int) ($_POST['id'] ?? 0);
                $password = $_POST['password'] ?? '';

                if (strlen($password) < 8) {
                    $message = t('error.password_min_length');
                    $messageType = 'error';
                } elseif ($userModel->updatePassword($id, $password)) {
                    $message = t('admin.users.password_updated');
                    $messageType = 'success';
                    Logger::write(Logger::ACTION_PASSWORD_CHANGE, Session::getUserId(), "Ändrade lösenord för användare ID: {$id}");
                } else {
                    $message = t('admin.users.password_update_failed');
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $id = (int) ($_POST['id'] ?? 0);

                // Förhindra att man tar bort sig själv
                if ($id === Session::getUserId()) {
                    $message = t('admin.users.cannot_delete_self');
                    $messageType = 'error';
                } elseif ($userModel->delete($id)) {
                    $message = t('admin.users.deleted');
                    $messageType = 'success';
                    Logger::write(Logger::ACTION_DELETE, Session::getUserId(), "Tog bort användare ID: {$id}");
                } else {
                    $message = t('admin.users.delete_failed');
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Hämta alla användare
$users = $userModel->findAll();
$editUser = null;

if (isset($_GET['edit'])) {
    $editUser = $userModel->findById((int) $_GET['edit']);
}
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.nav.users') ?> - <?= t('admin.title.prefix') ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <h1><?= t('admin.nav.users') ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Skapa/Redigera användare -->
        <div class="card">
            <h2><?php echo $editUser ? t('admin.users.edit') : t('admin.users.create_new'); ?></h2>
            <form method="POST" action="">
                <?php echo Session::csrfField(); ?>
                <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><?= t('field.name') ?></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($editUser['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><?= t('field.email') ?></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="role"><?= t('field.role') ?></label>
                        <select id="role" name="role">
                            <option value="user" <?php echo ($editUser['role'] ?? '') === 'user' ? 'selected' : ''; ?>><?= t('admin.users.role_user') ?></option>
                            <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>><?= t('admin.users.role_admin') ?></option>
                        </select>
                    </div>
                </div>

                <?php if (!$editUser): ?>
                <div class="form-group">
                    <label for="password"><?= t('field.password_min_8') ?></label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <?php endif; ?>

                <button type="submit" class="btn"><?php echo $editUser ? t('common.update') : t('common.create'); ?></button>
                <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-secondary"><?= t('common.cancel') ?></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($editUser): ?>
        <!-- Byt lösenord -->
        <div class="card">
            <h2><?= t('admin.users.change_password') ?></h2>
            <form method="POST" action="">
                <?php echo Session::csrfField(); ?>
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">

                <div class="form-group">
                    <label for="new_password"><?= t('field.new_password_min_8') ?></label>
                    <input type="password" id="new_password" name="password" required minlength="8">
                </div>

                <button type="submit" class="btn"><?= t('admin.users.change_password') ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Lista användare -->
        <div class="card">
            <h2><?= t('admin.users.all_users', ['count' => count($users)]) ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= t('field.name') ?></th>
                        <th><?= t('field.email') ?></th>
                        <th><?= t('field.role') ?></th>
                        <th><?= t('common.created') ?></th>
                        <th><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                        <td class="actions">
                            <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-secondary btn-small"><?= t('common.edit') ?></a>
                            <?php if ($user['id'] !== Session::getUserId()): ?>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('<?= t('confirm.are_you_sure') ?>');">
                                <?php echo Session::csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small"><?= t('common.delete') ?></button>
                            </form>
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