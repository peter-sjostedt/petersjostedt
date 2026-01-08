<?php
/**
 * Admin - Användarhantering
 */

require_once __DIR__ . '/../public_html/includes/config.php';

Session::start();
Session::requireAdmin('login.php');

$userModel = new User();
$message = '';
$messageType = '';

// Hantera åtgärder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Ogiltig förfrågan.';
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
                    $message = 'Alla fält måste fyllas i.';
                    $messageType = 'error';
                } elseif (strlen($password) < 8) {
                    $message = 'Lösenordet måste vara minst 8 tecken.';
                    $messageType = 'error';
                } else {
                    $userId = $userModel->create($email, $password, $name, $role);
                    if ($userId) {
                        $message = 'Användare skapad!';
                        $messageType = 'success';
                        Logger::write(Logger::ACTION_CREATE, Session::getUserId(), "Skapade användare: {$email}");
                    } else {
                        $message = 'Kunde inte skapa användare. E-posten kanske redan finns.';
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
                    $message = 'Användare uppdaterad!';
                    $messageType = 'success';
                    Logger::write(Logger::ACTION_UPDATE, Session::getUserId(), "Uppdaterade användare ID: {$id}");
                } else {
                    $message = 'Kunde inte uppdatera användare.';
                    $messageType = 'error';
                }
                break;

            case 'update_password':
                $id = (int) ($_POST['id'] ?? 0);
                $password = $_POST['password'] ?? '';

                if (strlen($password) < 8) {
                    $message = 'Lösenordet måste vara minst 8 tecken.';
                    $messageType = 'error';
                } elseif ($userModel->updatePassword($id, $password)) {
                    $message = 'Lösenord uppdaterat!';
                    $messageType = 'success';
                    Logger::write(Logger::ACTION_PASSWORD_CHANGE, Session::getUserId(), "Ändrade lösenord för användare ID: {$id}");
                } else {
                    $message = 'Kunde inte uppdatera lösenord.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $id = (int) ($_POST['id'] ?? 0);

                // Förhindra att man tar bort sig själv
                if ($id === Session::getUserId()) {
                    $message = 'Du kan inte ta bort dig själv.';
                    $messageType = 'error';
                } elseif ($userModel->delete($id)) {
                    $message = 'Användare borttagen!';
                    $messageType = 'success';
                    Logger::write(Logger::ACTION_DELETE, Session::getUserId(), "Tog bort användare ID: {$id}");
                } else {
                    $message = 'Kunde inte ta bort användare.';
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
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Användare - Admin</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #eee; min-height: 100vh; }
        .sidebar { position: fixed; left: 0; top: 0; width: 250px; height: 100vh; background: #16213e; padding: 2rem 0; }
        .sidebar h2 { color: #fff; padding: 0 1.5rem 1.5rem; border-bottom: 1px solid #0f3460; }
        .sidebar nav { margin-top: 1rem; }
        .sidebar a { display: block; padding: 1rem 1.5rem; color: #aaa; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 250px; padding: 2rem; }
        h1 { margin-bottom: 1.5rem; }
        .card { background: #16213e; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .card h2 { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #0f3460; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #aaa; }
        input, select { width: 100%; padding: 0.75rem; border: 1px solid #0f3460; border-radius: 4px; background: #1a1a2e; color: #eee; }
        input:focus, select:focus { outline: none; border-color: #e94560; }
        .btn { padding: 0.75rem 1.5rem; background: #e94560; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #c73e54; }
        .btn-secondary { background: #0f3460; }
        .btn-secondary:hover { background: #16213e; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.875rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #0f3460; }
        th { color: #aaa; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-admin { background: #e94560; }
        .badge-user { background: #0f3460; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; }
        .message.success { background: #28a74533; color: #28a745; }
        .message.error { background: #dc354533; color: #dc3545; }
        .actions { display: flex; gap: 0.5rem; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="users.php" class="active">Användare</a>
            <a href="settings.php">Inställningar</a>
            <a href="logs.php">Loggar</a>
            <a href="sessions.php">Sessioner</a>
            <a href="../public_html/">Visa sidan</a>
            <a href="logout.php">Logga ut</a>
        </nav>
    </aside>

    <main class="main">
        <h1>Användare</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Skapa/Redigera användare -->
        <div class="card">
            <h2><?php echo $editUser ? 'Redigera användare' : 'Skapa ny användare'; ?></h2>
            <form method="POST" action="">
                <?php echo Session::csrfField(); ?>
                <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Namn</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($editUser['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-post</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Roll</label>
                        <select id="role" name="role">
                            <option value="user" <?php echo ($editUser['role'] ?? '') === 'user' ? 'selected' : ''; ?>>Användare</option>
                            <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>

                <?php if (!$editUser): ?>
                <div class="form-group">
                    <label for="password">Lösenord (minst 8 tecken)</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <?php endif; ?>

                <button type="submit" class="btn"><?php echo $editUser ? 'Uppdatera' : 'Skapa'; ?></button>
                <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-secondary">Avbryt</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($editUser): ?>
        <!-- Byt lösenord -->
        <div class="card">
            <h2>Byt lösenord</h2>
            <form method="POST" action="">
                <?php echo Session::csrfField(); ?>
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">

                <div class="form-group">
                    <label for="new_password">Nytt lösenord (minst 8 tecken)</label>
                    <input type="password" id="new_password" name="password" required minlength="8">
                </div>

                <button type="submit" class="btn">Byt lösenord</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Lista användare -->
        <div class="card">
            <h2>Alla användare (<?php echo count($users); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Namn</th>
                        <th>E-post</th>
                        <th>Roll</th>
                        <th>Skapad</th>
                        <th>Åtgärder</th>
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
                            <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-secondary btn-small">Redigera</a>
                            <?php if ($user['id'] !== Session::getUserId()): ?>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Är du säker?');">
                                <?php echo Session::csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">Ta bort</button>
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
