<?php
/**
 * Test - Bilduppladdning
 */

require_once __DIR__ . '/includes/config.php';
secure_session_start();
set_security_headers();

$message = '';
$messageType = '';
$uploadedImage = null;

// Hantera uppladdning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!Session::validateCsrf($_POST['csrf_token'] ?? '')) {
        $message = t('error.csrf_invalid');
        $messageType = 'error';
    } elseif (isset($_FILES['image'])) {
        $folder = $_POST['folder'] ?? 'images';
        $imageUpload = ImageUpload::getInstance();

        $result = $imageUpload->upload($_FILES['image'], $folder);

        if ($result) {
            $uploadedImage = $result;
            $message = t('upload.success') . ': ' . htmlspecialchars($result['original_name']) . ' - Skapade 3 storlekar (thumbnail, medium, large)';
            if (isset($result['message'])) {
                $message .= ' - ' . $result['message'];
            }
            $messageType = 'success';
        } else {
            $message = $imageUpload->getError();
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

// Hämta uppladdade bilder
$allImages = Database::getInstance()->fetchAll(
    "SELECT * FROM files WHERE mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 20"
);

// Läs config
$appConfig = require __DIR__ . '/../config/app.php';
$maxSize = $appConfig['upload']['max_file_size'];
$sizes = $appConfig['images']['sizes'];
$gdAvailable = FileUpload::gdAvailable();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Bilduppladdning</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; padding: 2rem; background: #f5f5f5; }
        .container { max-width: 1100px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 1rem; color: #333; }
        h2 { margin-bottom: 1rem; color: #555; font-size: 1.3rem; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333; }
        input[type="file"], select { display: block; width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        button:hover { background: #0056b3; }
        button.danger { background: #dc3545; }
        button.danger:hover { background: #c82333; }
        .upload-result { background: #e7f3ff; padding: 1rem; border-radius: 4px; margin-top: 1rem; }
        .upload-result h3 { margin-bottom: 0.5rem; color: #0056b3; }
        .upload-result img { max-width: 100%; height: auto; border-radius: 4px; margin: 0.5rem 0; border: 2px solid #ddd; }
        .upload-result pre { background: white; padding: 0.75rem; border-radius: 4px; overflow-x: auto; font-size: 0.85rem; margin-top: 0.5rem; }
        .stats { display: flex; gap: 1.5rem; margin-top: 0.5rem; }
        .stat { background: white; padding: 0.5rem 1rem; border-radius: 4px; }
        .stat strong { display: block; color: #666; font-size: 0.85rem; }
        .stat span { font-size: 1.1rem; color: #007bff; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
        .image-item { background: white; border: 1px solid #ddd; border-radius: 4px; padding: 0.5rem; }
        .image-item img { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; }
        .image-item .info { padding: 0.5rem 0; font-size: 0.85rem; color: #666; }
        .image-item .info div { margin: 0.25rem 0; }
        .image-item .actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
        .image-item .actions a { font-size: 0.85rem; color: #007bff; text-decoration: none; }
        .image-item .actions button { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
        code { background: #f4f4f4; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Test - Bilduppladdning</h1>

        <?php if (!$gdAvailable): ?>
            <div class="message warning">
                ⚠️ GD-biblioteket är inte tillgängligt. Bilder kommer sparas i originalstorlek utan resize.
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Uppladdningsformulär -->
        <div class="card">
            <h2>Ladda upp bild</h2>

            <p style="margin-bottom: 1rem;">
                <strong><?= t('upload.max_size') ?>:</strong> <?= round($maxSize / 1024 / 1024, 1) ?> MB<br>
                <strong>GD tillgänglig:</strong> <?= $gdAvailable ? '✓ Ja' : '✗ Nej' ?><br>
                <strong>Tillåtna format:</strong> JPEG, PNG, GIF, WebP
            </p>

            <form method="POST" enctype="multipart/form-data">
                <?= Session::csrfField() ?>
                <input type="hidden" name="action" value="upload">

                <div class="form-group">
                    <label>Välj bild</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>

                <p style="background: #e7f3ff; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                    📦 <strong>Skapar automatiskt alla tre storlekar:</strong><br>
                    • Thumbnail: 150x150 (crop)<br>
                    • Medium: 600x600<br>
                    • Large: 1200x1200
                </p>

                <div class="form-group">
                    <label><?= t('upload.select_folder') ?></label>
                    <select name="folder">
                        <option value="images">images</option>
                        <option value="avatars">avatars</option>
                        <option value="gallery">gallery</option>
                    </select>
                </div>

                <button type="submit">Ladda upp bild</button>
            </form>

            <?php if ($uploadedImage): ?>
                <div class="upload-result">
                    <h3>✓ Uppladdad bild - Alla storlekar skapade</h3>

                    <?php if ($uploadedImage['resized']): ?>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1rem 0;">
                            <div style="text-align: center;">
                                <h4>Thumbnail (150x150)</h4>
                                <img src="serve.php?id=<?= $uploadedImage['thumbnail_id'] ?>" alt="Thumbnail" style="border: 2px solid #ddd;">
                                <p><small><?= round($uploadedImage['thumbnail']['file_size'] / 1024, 1) ?> KB</small></p>
                            </div>
                            <div style="text-align: center;">
                                <h4>Medium (600x600)</h4>
                                <img src="serve.php?id=<?= $uploadedImage['medium_id'] ?>" alt="Medium" style="max-width: 100%; border: 2px solid #ddd;">
                                <p><small><?= round($uploadedImage['medium']['file_size'] / 1024, 1) ?> KB</small></p>
                            </div>
                            <div style="text-align: center;">
                                <h4>Large (1200x1200)</h4>
                                <img src="serve.php?id=<?= $uploadedImage['large_id'] ?>" alt="Large" style="max-width: 100%; border: 2px solid #ddd;">
                                <p><small><?= round($uploadedImage['large']['file_size'] / 1024, 1) ?> KB</small></p>
                            </div>
                        </div>

                        <div class="stats">
                            <div class="stat">
                                <strong>Original</strong>
                                <span><?= $uploadedImage['original_width'] ?>x<?= $uploadedImage['original_height'] ?></span>
                            </div>
                            <div class="stat">
                                <strong>Original storlek</strong>
                                <span><?= round($uploadedImage['original_size'] / 1024, 1) ?> KB</span>
                            </div>
                            <div class="stat">
                                <strong>Thumbnail ID</strong>
                                <span><?= $uploadedImage['thumbnail_id'] ?></span>
                            </div>
                            <div class="stat">
                                <strong>Medium ID</strong>
                                <span><?= $uploadedImage['medium_id'] ?></span>
                            </div>
                            <div class="stat">
                                <strong>Large ID</strong>
                                <span><?= $uploadedImage['large_id'] ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="margin-top: 0.5rem; color: #856404;">
                            ⚠️ Bilden sparades i originalstorlek (GD ej tillgänglig)
                        </p>
                    <?php endif; ?>

                    <pre><?= htmlspecialchars(json_encode($uploadedImage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>

                    <p style="margin-top: 0.5rem;">
                        <a href="serve.php?id=<?= $uploadedImage['id'] ?>" target="_blank">Visa i nytt fönster</a> |
                        <a href="serve.php?id=<?= $uploadedImage['id'] ?>&download=1">Ladda ner</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lista över bilder -->
        <div class="card">
            <h2>Uppladdade bilder (senaste 20)</h2>

            <?php if (empty($allImages)): ?>
                <p style="color: #999;">Inga bilder uppladdade än.</p>
            <?php else: ?>
                <div class="image-grid">
                    <?php foreach ($allImages as $image): ?>
                        <div class="image-item">
                            <img src="serve.php?id=<?= $image['id'] ?>" alt="<?= htmlspecialchars($image['original_name']) ?>">
                            <div class="info">
                                <div><strong><?= htmlspecialchars($image['original_name']) ?></strong></div>
                                <div><?= round($image['file_size'] / 1024, 1) ?> KB</div>
                                <div><code><?= htmlspecialchars($image['mime_type']) ?></code></div>
                                <div><?= date('Y-m-d H:i', strtotime($image['created_at'])) ?></div>
                            </div>
                            <div class="actions">
                                <a href="serve.php?id=<?= $image['id'] ?>" target="_blank">Visa</a>
                                <a href="serve.php?id=<?= $image['id'] ?>&download=1">Ladda ner</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?= t('upload.delete_confirm') ?>');">
                                    <?= Session::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file_id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="danger">Radera</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <p style="text-align: center; color: #666;">
            <a href="test-file-upload.php">→ Test filuppladdning</a> |
            <a href="test-file-browser.php">→ Filbläddrare</a>
        </p>
    </div>
</body>
</html>
