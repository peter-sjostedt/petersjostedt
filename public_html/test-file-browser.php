<?php
/**
 * Test - Filbläddrare
 */

require_once __DIR__ . '/includes/config.php';
secure_session_start();
set_security_headers();

$message = '';
$messageType = '';

// Hantera radering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!Session::validateCsrf($_POST['csrf_token'] ?? '')) {
        $message = t('error.csrf_invalid');
        $messageType = 'error';
    } else {
        $fileId = (int)($_POST['file_id'] ?? 0);
        $fileUpload = FileUpload::getInstance();

        if ($fileUpload->delete($fileId)) {
            $message = t('upload.deleted');
            $messageType = 'success';
        } else {
            $message = $fileUpload->getError();
            $messageType = 'error';
        }
    }
}

// Hämta aktuell mapp
$currentFolder = $_GET['folder'] ?? '';
$currentFolder = str_replace(['..', '\\'], '', $currentFolder); // Säkerhet

// Läs config
$appConfig = require __DIR__ . '/../config/app.php';
$basePath = $appConfig['upload']['base_path'];

// Hämta mappar
$folders = [];
if (is_dir($basePath)) {
    $dirs = scandir($basePath);
    foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir($basePath . '/' . $dir)) {
            $folders[] = $dir;
        }
    }
}

// Hämta filer i aktuell mapp
if ($currentFolder) {
    $files = FileUpload::getByFolder($currentFolder);
} else {
    $files = FileUpload::getByFolder('');
}

// Beräkna statistik
$totalFiles = count($files);
$totalSize = array_sum(array_column($files, 'file_size'));
$imageCount = count(array_filter($files, fn($f) => str_starts_with($f['mime_type'], 'image/')));
$docCount = $totalFiles - $imageCount;
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Filbläddrare</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; padding: 2rem; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 1rem; color: #333; }
        h2 { margin-bottom: 1rem; color: #555; font-size: 1.3rem; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .breadcrumb { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1.5rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px; }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { color: #999; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: #f8f9fa; padding: 1rem; border-radius: 4px; border-left: 4px solid #007bff; }
        .stat strong { display: block; color: #666; font-size: 0.85rem; margin-bottom: 0.25rem; }
        .stat span { font-size: 1.5rem; color: #333; font-weight: 600; }
        .folders { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .folder-item { background: white; border: 2px solid #ddd; border-radius: 8px; padding: 1rem; text-align: center; cursor: pointer; transition: all 0.2s; }
        .folder-item:hover { border-color: #007bff; background: #f8f9fa; }
        .folder-item a { text-decoration: none; color: #333; display: block; }
        .folder-item .icon { font-size: 3rem; margin-bottom: 0.5rem; }
        .folder-item .name { font-weight: 500; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .file-icon { font-size: 1.5rem; margin-right: 0.5rem; }
        .actions { display: flex; gap: 0.5rem; align-items: center; }
        .actions a { color: #007bff; text-decoration: none; font-size: 0.9rem; }
        .actions a:hover { text-decoration: underline; }
        button.danger { background: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.85rem; }
        button.danger:hover { background: #c82333; }
        code { background: #f4f4f4; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.85rem; }
        .empty { text-align: center; padding: 3rem; color: #999; }
        .preview { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📂 Filbläddrare</h1>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="test-file-browser.php">🏠 Rot</a>
            <?php if ($currentFolder): ?>
                <span>/</span>
                <span><?= htmlspecialchars($currentFolder) ?></span>
            <?php endif; ?>
        </div>

        <!-- Statistik -->
        <div class="stats">
            <div class="stat">
                <strong>Totalt antal filer</strong>
                <span><?= $totalFiles ?></span>
            </div>
            <div class="stat">
                <strong>Total storlek</strong>
                <span><?= round($totalSize / 1024 / 1024, 2) ?> MB</span>
            </div>
            <div class="stat">
                <strong>Bilder</strong>
                <span><?= $imageCount ?></span>
            </div>
            <div class="stat">
                <strong>Dokument</strong>
                <span><?= $docCount ?></span>
            </div>
        </div>

        <!-- Mappar (visa endast i root) -->
        <?php if (!$currentFolder && !empty($folders)): ?>
        <div class="card">
            <h2>Mappar</h2>
            <div class="folders">
                <?php foreach ($folders as $folder): ?>
                    <div class="folder-item">
                        <a href="?folder=<?= urlencode($folder) ?>">
                            <div class="icon">📁</div>
                            <div class="name"><?= htmlspecialchars($folder) ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filer -->
        <div class="card">
            <h2>Filer <?= $currentFolder ? 'i ' . htmlspecialchars($currentFolder) : 'i roten' ?></h2>

            <?php if (empty($files)): ?>
                <div class="empty">
                    <p>📭 Inga filer i denna mapp</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Förhandsgranskning</th>
                            <th>Namn</th>
                            <th>Typ</th>
                            <th>Storlek</th>
                            <th>Uppladdad</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                            <?php
                            $isImage = str_starts_with($file['mime_type'], 'image/');
                            $icon = match (true) {
                                str_starts_with($file['mime_type'], 'image/') => '🖼️',
                                $file['mime_type'] === 'application/pdf' => '📄',
                                str_starts_with($file['mime_type'], 'text/') => '📝',
                                default => '📎'
                            };
                            ?>
                            <tr>
                                <td>
                                    <?php if ($isImage): ?>
                                        <img src="serve.php?id=<?= $file['id'] ?>" alt="" class="preview">
                                    <?php else: ?>
                                        <span class="file-icon"><?= $icon ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($file['original_name']) ?></td>
                                <td><code><?= htmlspecialchars($file['mime_type']) ?></code></td>
                                <td><?= round($file['file_size'] / 1024, 1) ?> KB</td>
                                <td><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="serve.php?id=<?= $file['id'] ?>" target="_blank">Visa</a>
                                    <a href="serve.php?id=<?= $file['id'] ?>&download=1">Ladda ner</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('<?= t('upload.delete_confirm') ?>');">
                                        <?= Session::csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                        <button type="submit" class="danger">Radera</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="background: #fff3cd; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
            <strong>💡 Tips:</strong> För återanvändbar filväljare som modal, se <a href="modal-usage.php" style="color: #0056b3; font-weight: 600;">Modal-exemplen</a>
        </div>

        <p style="text-align: center; color: #666;">
            <a href="test-file-upload.php">→ Test filuppladdning</a> |
            <a href="test-image-upload.php">→ Test bilduppladdning</a> |
            <a href="modal-usage.php">→ <strong>Filväljare Modal</strong></a>
        </p>
    </div>
</body>
</html>
