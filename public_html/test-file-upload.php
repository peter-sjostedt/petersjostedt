<?php
/**
 * Test - Filuppladdning
 */

require_once __DIR__ . '/includes/config.php';
secure_session_start();
set_security_headers();

$message = '';
$messageType = '';
$uploadedFile = null;

// Hantera uppladdning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!Session::validateCsrf($_POST['csrf_token'] ?? '')) {
        $message = t('error.csrf_invalid');
        $messageType = 'error';
    } elseif (isset($_FILES['file'])) {
        $folder = $_POST['folder'] ?? 'documents';
        $fileUpload = FileUpload::getInstance();

        $result = $fileUpload->upload($_FILES['file'], $folder);

        if ($result) {
            $uploadedFile = $result;
            $message = t('upload.success') . ': ' . htmlspecialchars($result['original_name']);
            $messageType = 'success';
        } else {
            $message = $fileUpload->getError();
            $messageType = 'error';
        }
    }
}

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

// Hämta uppladdade filer
$allFiles = Database::getInstance()->fetchAll('SELECT * FROM files ORDER BY created_at DESC LIMIT 20');

// Läs config
$appConfig = require __DIR__ . '/../config/app.php';
$maxSize = $appConfig['upload']['max_file_size'];
$allowedTypes = $appConfig['upload']['allowed_types'];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Filuppladdning</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; padding: 2rem; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 1rem; color: #333; }
        h2 { margin-bottom: 1rem; color: #555; font-size: 1.3rem; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333; }
        input[type="file"], select { display: block; width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        button:hover { background: #0056b3; }
        button.danger { background: #dc3545; }
        button.danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .file-info { font-size: 0.9rem; color: #666; }
        .actions { display: flex; gap: 0.5rem; align-items: center; }
        code { background: #f4f4f4; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.9rem; }
        .upload-result { background: #e7f3ff; padding: 1rem; border-radius: 4px; margin-top: 1rem; }
        .upload-result h3 { margin-bottom: 0.5rem; color: #0056b3; }
        .upload-result pre { background: white; padding: 0.75rem; border-radius: 4px; overflow-x: auto; font-size: 0.85rem; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 0.75rem; border-radius: 4px; margin-top: 0.5rem; display: none; }
    </style>
    <script>
        // Validera filtyp vid val
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('input[type="file"][name="file"]');
            const warningDiv = document.getElementById('file-warning');
            const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.pdf', '.txt'];

            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const fileName = file.name.toLowerCase();
                        const fileExt = '.' + fileName.split('.').pop();

                        if (!allowedExtensions.includes(fileExt)) {
                            warningDiv.textContent = '⚠️ Varning: ' + fileExt + ' är inte en tillåten filtyp. Uppladdning kommer att misslyckas.';
                            warningDiv.style.display = 'block';
                        } else if (file.size > <?= $maxSize ?>) {
                            warningDiv.textContent = '⚠️ Varning: Filen är för stor (' + (file.size / 1024 / 1024).toFixed(2) + ' MB). Max storlek är <?= round($maxSize / 1024 / 1024, 1) ?> MB.';
                            warningDiv.style.display = 'block';
                        } else {
                            warningDiv.style.display = 'none';
                        }
                    }
                });
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>🗂️ Test - Filuppladdning</h1>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Uppladdningsformulär -->
        <div class="card">
            <h2>Ladda upp fil</h2>

            <p style="margin-bottom: 1rem;">
                <strong><?= t('upload.max_size') ?>:</strong> <?= round($maxSize / 1024 / 1024, 1) ?> MB<br>
                <strong><?= t('upload.allowed_types') ?>:</strong><br>
                • Bilder: JPEG, PNG, GIF, WebP<br>
                • Dokument: PDF, Text
            </p>

            <form method="POST" enctype="multipart/form-data">
                <?= Session::csrfField() ?>
                <input type="hidden" name="action" value="upload">

                <div class="form-group">
                    <label><?= t('upload.select_file') ?></label>
                    <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt" required>
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        OBS: Endast bilder (JPEG, PNG, GIF, WebP), PDF och textfiler tillåts
                    </small>
                    <div id="file-warning" class="warning"></div>
                </div>

                <div class="form-group">
                    <label><?= t('upload.select_folder') ?></label>
                    <select name="folder">
                        <option value="documents">documents</option>
                        <option value="images">images</option>
                        <option value="misc">misc</option>
                    </select>
                </div>

                <button type="submit">Ladda upp</button>
            </form>

            <?php if ($uploadedFile): ?>
                <div class="upload-result">
                    <h3>✓ Uppladdad fil</h3>
                    <pre><?= htmlspecialchars(json_encode($uploadedFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                    <p style="margin-top: 0.5rem;">
                        <a href="serve.php?id=<?= $uploadedFile['id'] ?>" target="_blank">Visa fil</a> |
                        <a href="serve.php?id=<?= $uploadedFile['id'] ?>&download=1">Ladda ner</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lista över filer -->
        <div class="card">
            <h2>Uppladdade filer (senaste 20)</h2>

            <?php if (empty($allFiles)): ?>
                <p style="color: #999;">Inga filer uppladdade än.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Originalnamn</th>
                            <th>Typ</th>
                            <th>Storlek</th>
                            <th>Datum</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allFiles as $file): ?>
                            <tr>
                                <td><?= $file['id'] ?></td>
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
                                        <button type="submit" class="danger" style="padding: 0.25rem 0.5rem; font-size: 0.85rem;">Radera</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <p style="text-align: center; color: #666;">
            <a href="test-image-upload.php">→ Test bilduppladdning</a> |
            <a href="test-file-browser.php">→ Filbläddrare</a>
        </p>
    </div>
</body>
</html>
