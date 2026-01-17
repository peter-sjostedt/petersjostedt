<?php
/**
 * Admin - Organisationshantering
 * Hantera organisationer i Hospitex RFID-systemet
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
            case 'update':
                $id = strtoupper(trim($_POST['id'] ?? ''));
                $name = trim($_POST['name'] ?? '');
                $org_type = $_POST['org_type'] ?? 'customer';
                $address = trim($_POST['address'] ?? '') ?: null;
                $postal_code = trim($_POST['postal_code'] ?? '') ?: null;
                $city = trim($_POST['city'] ?? '') ?: null;
                $country = strtoupper(trim($_POST['country'] ?? 'SE'));
                $phone = trim($_POST['phone'] ?? '') ?: null;
                $email = trim($_POST['email'] ?? '') ?: null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                // Handle article_fields array
                $article_schema = null;
                if (isset($_POST['article_fields']) && is_array($_POST['article_fields'])) {
                    $fields = array_filter($_POST['article_fields'], function($field) {
                        return !empty($field['label']);
                    });
                    if (!empty($fields)) {
                        // Sortera efter sort_order
                        usort($fields, function($a, $b) {
                            return ((int)($a['sort_order'] ?? 0)) - ((int)($b['sort_order'] ?? 0));
                        });
                        $article_schema = json_encode(array_values($fields));
                    }
                }

                if (empty($id) || empty($name)) {
                    $message = t('error.all_fields_required');
                    $messageType = 'error';
                } elseif ($action === 'create') {
                    // Kontrollera om org-nummer redan finns
                    $stmt = $db->prepare("SELECT id FROM organizations WHERE id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->fetch()) {
                        $message = t('admin.organizations.message.id_exists');
                        $messageType = 'error';
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO organizations (id, name, org_type, article_schema, address, postal_code, city, country, phone, email, is_active)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");

                        if ($stmt->execute([$id, $name, $org_type, $article_schema, $address, $postal_code, $city, $country, $phone, $email, $is_active])) {
                            $message = t('admin.organizations.message.created');
                            $messageType = 'success';
                            Logger::getInstance()->info('ORG_CREATE', Session::getUserId(), "Skapade organisation: {$id} - {$name}");
                            header('Location: organizations.php');
                            exit;
                        } else {
                            $message = t('error.database_error');
                            $messageType = 'error';
                        }
                    }
                } else {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE organizations
                        SET name = ?, org_type = ?, article_schema = ?, address = ?, postal_code = ?, city = ?,
                            country = ?, phone = ?, email = ?, is_active = ?
                        WHERE id = ?
                    ");

                    if ($stmt->execute([$name, $org_type, $article_schema, $address, $postal_code, $city, $country, $phone, $email, $is_active, $id])) {
                        $message = t('admin.organizations.message.updated');
                        $messageType = 'success';
                        Logger::getInstance()->info('ORG_UPDATE', Session::getUserId(), "Uppdaterade organisation: {$id}");
                        header('Location: organizations.php');
                        exit;
                    } else {
                        $message = t('error.database_error');
                        $messageType = 'error';
                    }
                }
                break;

            case 'delete':
                $id = trim($_POST['id'] ?? '');

                // Förhindra att systemorganisationen raderas
                if ($id === 'SYSTEM') {
                    $message = t('admin.organizations.message.cannot_delete_system');
                    $messageType = 'error';
                } else {
                    $stmt = $db->prepare("DELETE FROM organizations WHERE id = ?");

                    if ($stmt->execute([$id])) {
                        $message = t('admin.organizations.message.deleted');
                        $messageType = 'success';
                        Logger::getInstance()->warning('ORG_DELETE', Session::getUserId(), "Raderade organisation: {$id}");
                    } else {
                        $message = t('error.database_error');
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Hantera sortering
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

// Validera sorteringskolumn
$allowedSort = ['id', 'name', 'org_type', 'is_active', 'created_at'];
if (!in_array($sortBy, $allowedSort)) {
    $sortBy = 'created_at';
}

// Validera sorteringsordning
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Hämta alla organisationer
try {
    $stmt = $db->query("
        SELECT o.*,
               COALESCE((SELECT COUNT(*) FROM units u WHERE u.organization_id = o.id), 0) as unit_count
        FROM organizations o
        ORDER BY {$sortBy} {$sortOrder}
    ");
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Om units-tabellen inte finns än, hämta utan unit_count
    $stmt = $db->query("
        SELECT o.*, 0 as unit_count
        FROM organizations o
        ORDER BY {$sortBy} {$sortOrder}
    ");
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Statistik
$totalOrgs = count($organizations);
$activeOrgs = count(array_filter($organizations, fn($o) => $o['is_active']));
$systemOrgs = count(array_filter($organizations, fn($o) => $o['org_type'] === 'system'));
$customerOrgs = count(array_filter($organizations, fn($o) => $o['org_type'] === 'customer'));
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.organizations.title') ?> - <?= t('admin.title.prefix') ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <meta name="org-labels" content='<?= json_encode([
        'id' => t('admin.organizations.form.id'),
        'id_help' => t('admin.organizations.form.id_help'),
        'name' => t('admin.organizations.form.name'),
        'type' => t('admin.organizations.form.type'),
        'type_customer' => t('admin.organizations.type.customer'),
        'type_supplier' => t('admin.organizations.type.supplier'),
        'type_laundry' => t('admin.organizations.type.laundry'),
        'type_system' => t('admin.organizations.type.system'),
        'article_schema' => t('admin.organizations.form.article_schema'),
        'article_schema_help' => t('admin.organizations.form.article_schema_help'),
        'article_schema_add' => t('admin.organizations.form.article_schema_add'),
        'article_schema_placeholder' => t('admin.organizations.form.article_schema_placeholder'),
        'article_schema_remove' => t('admin.organizations.form.article_schema_remove'),
        'address' => t('admin.organizations.form.address'),
        'postal_code' => t('admin.organizations.form.postal_code'),
        'city' => t('admin.organizations.form.city'),
        'country' => t('admin.organizations.form.country'),
        'phone' => t('admin.organizations.form.phone'),
        'email' => t('admin.organizations.form.email'),
        'is_active' => t('admin.organizations.form.is_active'),
        'create' => t('common.create'),
        'update' => t('common.update'),
        'cancel' => t('common.cancel'),
        'modal_create' => t('admin.organizations.modal.create.title'),
        'modal_edit' => t('admin.organizations.modal.edit.title')
    ]) ?>'>
    <link rel="stylesheet" href="<?= versioned('admin/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= versioned('assets/css/modal.css') ?>">
    <script src="<?= versioned('assets/js/modal.js') ?>"></script>
    <script src="<?= versioned('assets/js/qr.js') ?>"></script>
    <script src="<?= versioned('admin/js/admin.js') ?>" defer></script>
    <script src="<?= versioned('admin/js/organizations.js') ?>" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1><?= t('admin.organizations.heading') ?></h1>
            <button type="button" class="btn btn-primary" id="createOrgBtn"><?= t('admin.organizations.action.create') ?></button>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Lista organisationer -->
        <div class="card">
            <h2><?= t('admin.organizations.title') ?> (<?= count($organizations) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>
                            <a href="?sort=id&order=<?= $sortBy === 'id' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('admin.organizations.table.id') ?>
                                <?php if ($sortBy === 'id'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=name&order=<?= $sortBy === 'name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('admin.organizations.table.name') ?>
                                <?php if ($sortBy === 'name'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=org_type&order=<?= $sortBy === 'org_type' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('admin.organizations.table.type') ?>
                                <?php if ($sortBy === 'org_type'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=is_active&order=<?= $sortBy === 'is_active' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('admin.organizations.table.status') ?>
                                <?php if ($sortBy === 'is_active'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=created_at&order=<?= $sortBy === 'created_at' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" style="color: inherit; text-decoration: none;">
                                <?= t('admin.organizations.table.created') ?>
                                <?php if ($sortBy === 'created_at'): ?>
                                    <?= $sortOrder === 'ASC' ? '▲' : '▼' ?>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th><?= t('admin.organizations.table.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizations as $org): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($org['id']) ?></code></td>
                            <td><strong><?= htmlspecialchars($org['name']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= $org['org_type'] === 'system' ? 'primary' : 'secondary' ?>">
                                    <?= t('admin.organizations.type.' . $org['org_type']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($org['is_active']): ?>
                                    <span class="badge badge-success"><?= t('admin.organizations.status.active') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?= t('admin.organizations.status.inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($org['created_at'])) ?></td>
                            <td class="actions">
                                <a href="users.php?org=<?= urlencode($org['id']) ?>" class="btn btn-icon" title="<?= t('admin.organizations.action.view_users') ?>">👤</a>
                                <a href="units.php?org_id=<?= urlencode($org['id']) ?>" class="btn btn-icon" title="<?= t('admin.organizations.action.view_units') ?>">🏢</a>
                                <button type="button" class="btn btn-icon" data-org-qr='<?= htmlspecialchars(json_encode(['id' => $org['id'], 'name' => $org['name']]), ENT_QUOTES) ?>' title="<?= t('admin.organizations.action.qr') ?>">📱</button>
                                <button type="button" class="btn btn-icon" data-org-edit='<?= json_encode($org) ?>' title="<?= t('admin.organizations.action.edit') ?>">✏️</button>
                                <?php if ($org['id'] !== 'SYSTEM'): ?>
                                    <form method="POST" style="display:inline;" data-confirm="<?= t('admin.organizations.modal.delete.message', ['name' => htmlspecialchars($org['name'])]) ?>">
                                        <?= Session::csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($org['id']) ?>">
                                        <button type="submit" class="btn btn-icon btn-icon-danger" title="<?= t('admin.organizations.action.delete') ?>">🗑️</button>
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
