<?php
/**
 * Exempel på språkhantering
 */

require_once __DIR__ . '/../src/Language.php';
require_once __DIR__ . '/../src/LanguageExport.php';

// Hämta instans
$lang = Language::getInstance();
$export = new LanguageExport();
$message = '';

// Byt språk
if (isset($_GET['lang'])) {
    $lang->setLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Exportera CSV
if (isset($_GET['export'])) {
    $export->downloadCSV('translations_' . date('Y-m-d') . '.csv');
    exit;
}

// Importera CSV
if (isset($_POST['import']) && isset($_FILES['csv_file'])) {
    $result = $export->importUploadedCSV($_FILES['csv_file']);
    
    if ($result['success']) {
        $message = "Import klar! {$result['added']} nya, {$result['updated']} uppdaterade.";
        if (!empty($result['languages_added'])) {
            $message .= " Nya språk: " . implode(', ', $result['languages_added']);
        }
    } else {
        $message = "Fel: " . implode(', ', $result['errors']);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang->t('welcome') ?></title>
    <link rel="stylesheet" href="/assets/css/modal.css">
    <style>
        body { font-family: system-ui, sans-serif; padding: 40px; }
        .lang-switcher { margin-bottom: 30px; }
        .lang-switcher a { 
            padding: 8px 16px; 
            margin-right: 8px; 
            text-decoration: none;
            border-radius: 4px;
            background: #eee;
        }
        .lang-switcher a.active { background: #3498db; color: white; }
        .examples { margin-top: 20px; }
        .examples p { margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 8px; border-radius: 4px; }
        .export-import { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .export-import h3 { margin-top: 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .message { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; background: #d4edda; color: #155724; }
        .file-input { margin: 10px 0; }
    </style>
</head>
<body>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Språkväljare -->
    <div class="lang-switcher">
        <?php foreach ($lang->getLanguages() as $code => $info): ?>
            <a href="?lang=<?= $code ?>" 
               class="<?= $code === $lang->getLanguage() ? 'active' : '' ?>">
                <?= $info['flag'] ?> <?= $info['name'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <h1><?= $lang->t('welcome') ?></h1>
    
    <div class="examples">
        <p><code>t('welcome')</code> → <?= $lang->t('welcome') ?></p>
        <p><code>t('welcome_user', ['name' => 'Peter'])</code> → <?= $lang->t('welcome_user', ['name' => 'Peter']) ?></p>
        <p><code>t('save')</code> → <?= $lang->t('save') ?></p>
        <p><code>t('cancel')</code> → <?= $lang->t('cancel') ?></p>
        <p><code>t('user.delete_confirm', ['name' => 'Anna'])</code> → <?= $lang->t('user.delete_confirm', ['name' => 'Anna']) ?></p>
    </div>

    <!-- Export/Import -->
    <div class="export-import">
        <h3>Export / Import</h3>
        
        <a href="?export=1" class="btn btn-primary">📥 Exportera CSV</a>
        
        <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
            <input type="file" name="csv_file" accept=".csv" class="file-input">
            <button type="submit" name="import" class="btn btn-success">📤 Importera CSV</button>
        </form>
    </div>

    <h2>Modal test</h2>
    <button onclick="testModal()"><?= $lang->t('delete') ?></button>

    <script src="/assets/js/modal.js"></script>
    <script>
        // Översättningar tillgängliga i JS
        const LANG = <?= json_encode($lang->all()) ?>;
        
        async function testModal() {
            const confirmed = await Modal.confirm(
                LANG['modal.confirm'],
                LANG['user.delete_confirm'].replace('{name}', 'Anna'),
                {
                    confirmText: LANG['delete'],
                    cancelText: LANG['cancel'],
                    danger: true
                }
            );
            
            if (confirmed) {
                Modal.success('OK', LANG['welcome']);
            }
        }
    </script>
</body>
</html>