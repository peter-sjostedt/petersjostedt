<?php
/**
 * Exempel - Integration av File Picker Modal i formulär
 */

require_once __DIR__ . '/includes/config.php';
secure_session_start();
set_security_headers();

$message = '';
$messageType = '';

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!Session::validateCsrf($_POST['csrf_token'] ?? '')) {
        $message = t('error.csrf_invalid');
        $messageType = 'error';
    } else {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $featuredImageId = (int)($_POST['featured_image_id'] ?? 0);
        $galleryIds = $_POST['gallery_ids'] ?? '';

        // Här skulle du spara till databasen
        $message = "Sparad! Titel: $title, Bild-ID: $featuredImageId, Galleri: $galleryIds";
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Picker Modal - Integration</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/file-picker-modal.css?v=5">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { margin-bottom: 0.5rem; color: #333; }
        .subtitle { color: #666; margin-bottom: 2rem; }
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-picker-field {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }
        .file-picker-preview {
            flex: 1;
            min-height: 100px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            color: #999;
            position: relative;
            overflow: hidden;
        }
        .file-picker-preview img {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }
        .file-picker-preview .placeholder {
            text-align: center;
            padding: 2rem;
        }
        .file-picker-preview .remove-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #dc3545;
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            display: none;
        }
        .file-picker-preview.has-image .remove-btn {
            display: block;
        }
        .file-picker-preview.has-image .placeholder {
            display: none;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            white-space: nowrap;
        }
        .btn:hover { background: #0056b3; }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover { background: #545b62; }
        .btn-success {
            background: #28a745;
            font-weight: 600;
        }
        .btn-success:hover { background: #218838; }

        .gallery-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-item .remove-btn {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: #dc3545;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }
        .gallery-placeholder {
            padding: 2rem;
            text-align: center;
            color: #999;
            border: 2px dashed #ddd;
            border-radius: 6px;
            background: #f9fafb;
            margin-top: 0.75rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 File Picker Modal - Formulärintegration</h1>
        <p class="subtitle">Exempel på hur filväljaren kan integreras i ett formulär</p>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <?= Session::csrfField() ?>
                <input type="hidden" name="action" value="save">

                <!-- Titel -->
                <div class="form-group">
                    <label>Titel</label>
                    <input type="text" name="title" placeholder="Skriv en titel..." required>
                </div>

                <!-- Beskrivning -->
                <div class="form-group">
                    <label>Beskrivning</label>
                    <textarea name="description" placeholder="Beskriv innehållet..."></textarea>
                </div>

                <!-- Utvald bild (en bild) -->
                <div class="form-group">
                    <label>Utvald bild</label>
                    <div class="file-picker-field">
                        <div class="file-picker-preview" id="featured-preview">
                            <div class="placeholder">
                                <div>📷</div>
                                <div>Ingen bild vald</div>
                            </div>
                            <img id="featured-image" style="display: none;" alt="">
                            <button type="button" class="remove-btn" id="remove-featured">×</button>
                        </div>
                        <button type="button" class="btn" id="select-featured">Välj bild</button>
                    </div>
                    <input type="hidden" name="featured_image_id" id="featured-image-id">
                </div>

                <!-- Bildgalleri (flera bilder) -->
                <div class="form-group">
                    <label>Bildgalleri</label>
                    <button type="button" class="btn-secondary btn" id="select-gallery">Lägg till bilder</button>
                    <div id="gallery-preview" class="gallery-placeholder">
                        Inga bilder i galleriet ännu
                    </div>
                    <input type="hidden" name="gallery_ids" id="gallery-ids">
                </div>

                <!-- Dokument/PDF (endast dokument) -->
                <div class="form-group">
                    <label>Bifogad fil (PDF eller text)</label>
                    <div class="file-picker-field">
                        <div class="file-picker-preview" id="document-preview">
                            <div class="placeholder">
                                <div>📄</div>
                                <div>Ingen fil vald</div>
                            </div>
                            <div id="document-info" style="display: none; padding: 1rem;"></div>
                            <button type="button" class="remove-btn" id="remove-document">×</button>
                        </div>
                        <button type="button" class="btn" id="select-document">Välj fil</button>
                    </div>
                    <input type="hidden" name="document_id" id="document-id">
                </div>

                <!-- Åtgärder -->
                <div class="form-actions">
                    <button type="reset" class="btn-secondary btn">Rensa</button>
                    <button type="submit" class="btn-success btn">Spara formulär</button>
                </div>
            </form>
        </div>

        <p style="text-align: center; color: #666;">
            <a href="modal-usage.php">→ Tillbaka till exempel</a> |
            <a href="test-file-upload.php">→ Test filuppladdning</a>
        </p>
    </div>

    <script src="assets/js/file-picker-modal.js"></script>
    <script src="assets/js/test-modal-integration.js"></script>
</body>
</html>
