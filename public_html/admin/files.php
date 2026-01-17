<?php
/**
 * Admin - Filhantering
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

// Hantera radering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = t('error.invalid_request');
        $messageType = 'error';
    } else {
        $action = $_POST['action'];

        if ($action === 'delete') {
            $fileId = (int)($_POST['file_id'] ?? 0);

            // Hämta filinfo
            $stmt = $db->prepare("SELECT file_path FROM files WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                // file_path är relativt från uploads/, t.ex. "documents/abc.pdf"
                $fullPath = __DIR__ . '/../uploads/' . $file['file_path'];

                // Ta bort fil från disk
                $fileDeleted = false;
                if (file_exists($fullPath)) {
                    $fileDeleted = unlink($fullPath);
                } else {
                    $fileDeleted = true; // Filen finns inte, fortsätt ändå
                }

                if ($fileDeleted) {
                    // Ta bort från databas
                    $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
                    if ($stmt->execute([$fileId])) {
                        $message = 'Filen har raderats';
                        $messageType = 'success';
                        Logger::write(Logger::ACTION_DELETE, Session::getUserId(), "Raderade fil ID: {$fileId}");
                    } else {
                        $message = 'Kunde inte radera filen från databasen';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Kunde inte radera filen från servern';
                    $messageType = 'error';
                }
            } else {
                $message = 'Filen hittades inte';
                $messageType = 'error';
            }
        } elseif ($action === 'delete_orphaned') {
            $filePath = $_POST['file_path'] ?? '';

            if (!empty($filePath) && str_starts_with($filePath, 'uploads/')) {
                $fullPath = __DIR__ . '/../' . $filePath;

                if (file_exists($fullPath)) {
                    if (unlink($fullPath)) {
                        $message = 'Orphaned fil har raderats från servern';
                        $messageType = 'success';
                        Logger::write(Logger::ACTION_DELETE, Session::getUserId(), "Raderade orphaned fil: {$filePath}");
                    } else {
                        $message = 'Kunde inte radera filen från servern';
                        $messageType = 'error';
                    }
                } else {
                    $message = t('admin.files.message.file_not_found');
                    $messageType = 'error';
                }
            } else {
                $message = t('admin.files.message.invalid_path');
                $messageType = 'error';
            }
        } elseif ($action === 'rename') {
            $fileId = (int)($_POST['file_id'] ?? 0);
            $newName = trim($_POST['new_name'] ?? '');

            if ($fileId && !empty($newName)) {
                // Hämta filinfo
                $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
                $stmt->execute([$fileId]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($file) {
                    // Validera filnamn (inga farliga tecken)
                    $newName = preg_replace('/[^a-zA-Z0-9._\-åäöÅÄÖ ]/', '', $newName);

                    // Behåll filändelsen från originalnamnet
                    $oldExt = pathinfo($file['original_name'], PATHINFO_EXTENSION);
                    $newExt = pathinfo($newName, PATHINFO_EXTENSION);

                    // Om användaren inte angav filändelse, lägg till den gamla
                    if (empty($newExt) && !empty($oldExt)) {
                        $newName .= '.' . $oldExt;
                    }

                    // Uppdatera endast original_name i databasen
                    $stmt = $db->prepare("UPDATE files SET original_name = ? WHERE id = ?");
                    if ($stmt->execute([$newName, $fileId])) {
                        $message = t('admin.files.message.name_updated');
                        $messageType = 'success';
                        Logger::write(Logger::ACTION_UPDATE, Session::getUserId(), "Bytte namn på fil ID {$fileId}: {$file['original_name']} → {$newName}");
                    } else {
                        $message = t('admin.files.message.name_update_failed');
                        $messageType = 'error';
                    }
                } else {
                    $message = t('admin.files.message.file_not_found');
                    $messageType = 'error';
                }
            } else {
                $message = t('admin.files.message.invalid_name');
                $messageType = 'error';
            }
        } elseif ($action === 'import_orphaned') {
            $filePath = $_POST['file_path'] ?? '';
            $dbPath = $_POST['db_path'] ?? '';

            if (!empty($filePath) && !empty($dbPath) && str_starts_with($filePath, 'uploads/')) {
                $fullPath = __DIR__ . '/../' . $filePath;

                if (file_exists($fullPath)) {
                    // Hämta filinfo
                    $fileSize = filesize($fullPath);
                    $originalName = basename($fullPath);

                    // Försök detektera MIME-typ
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $fullPath);
                        finfo_close($finfo);
                    } elseif (function_exists('mime_content_type')) {
                        $mimeType = mime_content_type($fullPath);
                    } else {
                        // Fallback: gissa från filändelse
                        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                        $mimeType = match($ext) {
                            'jpg', 'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                            'pdf' => 'application/pdf',
                            'txt' => 'text/plain',
                            default => 'application/octet-stream'
                        };
                    }

                    // Importera till databas
                    $userId = Session::getUserId();
                    $stmt = $db->prepare("
                        INSERT INTO files (original_name, stored_name, mime_type, file_size, file_path, folder, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $storedName = basename($fullPath);
                    $folder = dirname($dbPath);
                    if ($folder === '.') $folder = null;

                    if ($stmt->execute([$originalName, $storedName, $mimeType, $fileSize, $dbPath, $folder, $userId])) {
                        $message = 'Filen har importerats till databasen';
                        $messageType = 'success';
                        Logger::write(Logger::ACTION_CREATE, Session::getUserId(), "Importerade fil: {$originalName}");
                    } else {
                        $message = 'Kunde inte importera filen till databasen';
                        $messageType = 'error';
                    }
                } else {
                    $message = t('admin.files.message.file_not_found');
                    $messageType = 'error';
                }
            } else {
                $message = t('admin.files.message.invalid_path');
                $messageType = 'error';
            }
        }
    }
}

// Filtrera efter mapp
$folder = $_GET['folder'] ?? '';
$type = $_GET['type'] ?? 'all';

// Bygg query
$conditions = ["1=1"];
$params = [];

if (!empty($folder)) {
    $conditions[] = "folder = ?";
    $params[] = $folder;
}

switch ($type) {
    case 'image':
        $conditions[] = "mime_type LIKE 'image/%'";
        break;
    case 'document':
        $conditions[] = "(mime_type = 'application/pdf' OR mime_type LIKE 'text/%')";
        break;
}

$where = implode(' AND ', $conditions);

// Hämta filer
$stmt = $db->prepare("
    SELECT * FROM files
    WHERE {$where}
    ORDER BY created_at DESC
");
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hämta mappar
$stmt = $db->query("SELECT DISTINCT folder FROM files WHERE folder IS NOT NULL AND folder != '' ORDER BY folder");
$folders = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Hämta statistik
$stmt = $db->query("SELECT COUNT(*) as total, SUM(file_size) as total_size FROM files");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Skanna diskrepanser (om requested)
$showDiscrepancies = isset($_GET['show_discrepancies']);
$missingOnDisk = [];
$orphanedFiles = [];

if ($showDiscrepancies) {
    $uploadsDir = __DIR__ . '/../uploads';

    // 1. Hitta filer i DB som saknas på disk
    $stmt = $db->query("SELECT id, file_path, original_name FROM files");
    $dbFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbFiles as $file) {
        // file_path är relativt från uploads/
        $fullPath = __DIR__ . '/../uploads/' . $file['file_path'];
        if (!file_exists($fullPath)) {
            $missingOnDisk[] = $file;
        }
    }

    // 2. Hitta filer på disk som saknas i DB
    if (is_dir($uploadsDir)) {
        // Säkerhetsfiler som ska ignoreras
        $ignoreFiles = ['.htaccess', 'index.php'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();

                // Skippa säkerhetsfiler
                if (in_array($filename, $ignoreFiles)) {
                    continue;
                }

                // Relativt från uploads/, t.ex. "documents/abc.pdf"
                $relativeFromUploads = str_replace('\\', '/', substr($file->getPathname(), strlen($uploadsDir) + 1));

                // Kolla om filen finns i databasen
                $stmt = $db->prepare("SELECT id FROM files WHERE file_path = ?");
                $stmt->execute([$relativeFromUploads]);

                if (!$stmt->fetch()) {
                    $orphanedFiles[] = [
                        'path' => 'uploads/' . $relativeFromUploads, // För visning och radering
                        'db_path' => $relativeFromUploads, // Vad som skulle sparas i DB
                        'folder' => 'uploads/' . dirname($relativeFromUploads), // Bara mappen
                        'full_path' => $file->getPathname(),
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime()
                    ];
                }
            }
        }
    }
}

// Funktion för att formatera filstorlek
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin.files.title') ?> - <?= t('admin.title.prefix') ?></title>
    <link rel="stylesheet" href="<?= versioned('admin/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= versioned('admin/css/files.css') ?>">
    <link rel="stylesheet" href="<?= versioned('assets/css/modal.css') ?>">
    <script src="<?= versioned('admin/js/admin.js') ?>" defer></script>
    <script src="<?= versioned('admin/js/files.js') ?>" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <h1>📁 <?= t('admin.files.heading') ?></h1>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total']) ?></div>
                <div class="stat-label"><?= t('admin.files.stats.total_files') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= formatBytes($stats['total_size']) ?></div>
                <div class="stat-label"><?= t('admin.files.stats.total_size') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($folders) ?></div>
                <div class="stat-label"><?= t('admin.files.stats.folders') ?></div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card">
            <h2>🔍 <?= t('admin.files.filter.heading') ?></h2>
            <form method="GET" class="filter-form" id="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="folder"><?= t('admin.files.filter.folder') ?></label>
                        <select id="folder" name="folder" class="auto-submit">
                            <option value=""><?= t('admin.files.filter.all_folders') ?></option>
                            <?php foreach ($folders as $f): ?>
                                <option value="<?= htmlspecialchars($f) ?>" <?= $folder === $f ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type"><?= t('admin.files.filter.type') ?></label>
                        <select id="type" name="type" class="auto-submit">
                            <option value="all" <?= $type === 'all' ? 'selected' : '' ?>><?= t('admin.files.filter.all_types') ?></option>
                            <option value="image" <?= $type === 'image' ? 'selected' : '' ?>><?= t('admin.files.filter.images') ?></option>
                            <option value="document" <?= $type === 'document' ? 'selected' : '' ?>><?= t('admin.files.filter.documents') ?></option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="files.php" class="btn btn-secondary"><?= t('admin.files.filter.clear') ?></a>
                    <a href="files.php?show_discrepancies=1" class="btn btn-secondary">🔍 <?= t('admin.files.filter.scan') ?></a>
                </div>
            </form>
        </div>

        <?php if ($showDiscrepancies): ?>
        <!-- Diskrepanser -->
        <div class="card">
            <h2>⚠️ <?= t('admin.files.discrepancy.heading') ?></h2>

            <?php if (empty($missingOnDisk) && empty($orphanedFiles)): ?>
                <div class="message success">✓ Inga diskrepanser hittades! Alla filer är synkroniserade.</div>
            <?php else: ?>

                <?php if (!empty($missingOnDisk)): ?>
                <div class="discrepancy-section">
                    <h3>🔴 <?= t('admin.files.discrepancy.missing_on_disk', ['count' => count($missingOnDisk)]) ?></h3>
                    <p class="help-text"><?= t('admin.files.discrepancy.help_missing') ?></p>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= t('admin.files.table.filename') ?></th>
                                <th><?= t('admin.files.table.path') ?></th>
                                <th><?= t('admin.files.table.actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($missingOnDisk as $file): ?>
                            <tr>
                                <td><?= $file['id'] ?></td>
                                <td><?= htmlspecialchars($file['original_name']) ?></td>
                                <td><code><?= htmlspecialchars($file['file_path']) ?></code></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-small delete-db-btn" data-file-id="<?= $file['id'] ?>" data-file-name="<?= htmlspecialchars($file['original_name']) ?>">
                                        Ta bort från DB
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (!empty($orphanedFiles)): ?>
                <div class="discrepancy-section">
                    <h3>🟡 <?= t('admin.files.discrepancy.orphaned', ['count' => count($orphanedFiles)]) ?></h3>
                    <p class="help-text"><?= t('admin.files.discrepancy.help_orphaned') ?></p>
                    <table>
                        <thead>
                            <tr>
                                <th><?= t('admin.files.table.filename') ?></th>
                                <th><?= t('admin.files.table.path') ?></th>
                                <th><?= t('admin.files.table.size') ?></th>
                                <th><?= t('admin.files.table.modified') ?></th>
                                <th><?= t('admin.files.table.actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orphanedFiles as $file): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['name']) ?></td>
                                <td><code><?= htmlspecialchars($file['folder']) ?></code></td>
                                <td><?= formatBytes($file['size']) ?></td>
                                <td><?= date('Y-m-d H:i', $file['modified']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-small import-orphaned-btn"
                                            data-file-path="<?= htmlspecialchars($file['path']) ?>"
                                            data-db-path="<?= htmlspecialchars($file['db_path']) ?>"
                                            data-file-name="<?= htmlspecialchars($file['name']) ?>">
                                        <?= t('admin.files.action.import') ?>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-small delete-orphaned-btn"
                                            data-file-path="<?= htmlspecialchars($file['path']) ?>"
                                            data-file-name="<?= htmlspecialchars($file['name']) ?>">
                                        <?= t('admin.files.action.delete') ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Fillista -->
        <div class="card">
            <div class="files-header">
                <h2>📄 <?= t('admin.files.list.heading', ['count' => count($files)]) ?></h2>
                <div class="view-toggle">
                    <button type="button" class="btn btn-small view-toggle-btn active" data-view="grid">
                        <?= t('admin.files.view.grid') ?>
                    </button>
                    <button type="button" class="btn btn-small view-toggle-btn" data-view="table">
                        <?= t('admin.files.view.table') ?>
                    </button>
                </div>
            </div>

            <?php if (empty($files)): ?>
                <p class="empty-state"><?= t('admin.files.list.empty') ?></p>
            <?php else: ?>
                <div class="files-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="file-card">
                            <div class="file-preview">
                                <?php if (str_starts_with($file['mime_type'], 'image/')): ?>
                                    <img src="../<?= htmlspecialchars($file['file_path']) ?>" alt="<?= htmlspecialchars($file['original_name']) ?>">
                                <?php elseif ($file['mime_type'] === 'application/pdf'): ?>
                                    <div class="file-icon">📄</div>
                                <?php else: ?>
                                    <div class="file-icon">📎</div>
                                <?php endif; ?>
                            </div>

                            <div class="file-info">
                                <div class="file-name" title="<?= htmlspecialchars($file['original_name']) ?>">
                                    <?= htmlspecialchars($file['original_name']) ?>
                                </div>
                                <div class="file-meta">
                                    <span><?= formatBytes($file['file_size']) ?></span>
                                    <?php if ($file['folder']): ?>
                                        <span class="file-folder">📁 <?= htmlspecialchars($file['folder']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="file-date">
                                    <?= date('Y-m-d H:i', strtotime($file['created_at'])) ?>
                                </div>
                            </div>

                            <div class="file-actions">
                                <a href="https://petersjostedt.se/serve.php?id=<?= $file['id'] ?>" target="_blank" class="btn btn-secondary btn-small"><?= t('admin.files.action.view') ?></a>
                                <button type="button" class="btn btn-primary btn-small rename-file-btn"
                                        data-file-id="<?= $file['id'] ?>"
                                        data-file-name="<?= htmlspecialchars($file['original_name']) ?>">
                                    <?= t('admin.files.action.rename') ?>
                                </button>
                                <button type="button" class="btn btn-danger btn-small delete-file-btn"
                                        data-file-id="<?= $file['id'] ?>"
                                        data-file-name="<?= htmlspecialchars($file['original_name']) ?>">
                                    <?= t('admin.files.action.delete') ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tabellvy (dold som standard) -->
                <div class="files-table" style="display: none;">
                    <table>
                        <thead>
                            <tr>
                                <th><?= t('admin.files.table.preview') ?></th>
                                <th><?= t('admin.files.table.filename') ?></th>
                                <th><?= t('admin.files.table.folder') ?></th>
                                <th><?= t('admin.files.table.size') ?></th>
                                <th><?= t('admin.files.table.date') ?></th>
                                <th><?= t('admin.files.table.actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td class="table-preview">
                                    <?php if (str_starts_with($file['mime_type'], 'image/')): ?>
                                        <img src="../<?= htmlspecialchars($file['file_path']) ?>" alt="<?= htmlspecialchars($file['original_name']) ?>" class="table-preview-img">
                                    <?php elseif ($file['mime_type'] === 'application/pdf'): ?>
                                        <span class="table-icon">📄</span>
                                    <?php else: ?>
                                        <span class="table-icon">📎</span>
                                    <?php endif; ?>
                                </td>
                                <td class="file-name-cell">
                                    <strong><?= htmlspecialchars($file['original_name']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($file['folder']): ?>
                                        <span class="folder-badge">📁 <?= htmlspecialchars($file['folder']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatBytes($file['file_size']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                                <td class="table-actions">
                                    <a href="https://petersjostedt.se/serve.php?id=<?= $file['id'] ?>" target="_blank" class="btn btn-secondary btn-small" title="Visa">👁️</a>
                                    <button type="button" class="btn btn-primary btn-small rename-file-btn"
                                            data-file-id="<?= $file['id'] ?>"
                                            data-file-name="<?= htmlspecialchars($file['original_name']) ?>" title="Byt namn">✏️</button>
                                    <button type="button" class="btn btn-danger btn-small delete-file-btn"
                                            data-file-id="<?= $file['id'] ?>"
                                            data-file-name="<?= htmlspecialchars($file['original_name']) ?>" title="Radera">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Radera-formulär (dolt) -->
    <form id="delete-form" method="POST" style="display: none;">
        <?= Session::csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="file_id" id="delete-file-id">
    </form>

    <!-- Byt namn-formulär (dolt) -->
    <form id="rename-form" method="POST" style="display: none;">
        <?= Session::csrfField() ?>
        <input type="hidden" name="action" value="rename">
        <input type="hidden" name="file_id" id="rename-file-id">
        <input type="hidden" name="new_name" id="rename-new-name">
    </form>

    <!-- Radera orphaned fil-formulär (dolt) -->
    <form id="delete-orphaned-form" method="POST" style="display: none;">
        <?= Session::csrfField() ?>
        <input type="hidden" name="action" value="delete_orphaned">
        <input type="hidden" name="file_path" id="delete-orphaned-path">
    </form>

    <!-- Importera orphaned fil-formulär (dolt) -->
    <form id="import-orphaned-form" method="POST" style="display: none;">
        <?= Session::csrfField() ?>
        <input type="hidden" name="action" value="import_orphaned">
        <input type="hidden" name="file_path" id="import-orphaned-path">
        <input type="hidden" name="db_path" id="import-orphaned-db-path">
    </form>

    <!-- Import Modal -->
    <div id="import-modal" class="modal-overlay">
        <div class="modal">
            <button class="modal-close" data-action="cancel">&times;</button>
            <div class="modal-header warning">
                <div class="modal-icon">📥</div>
                <h3 class="modal-title"><?= t('admin.files.modal.import.title') ?></h3>
            </div>
            <div class="modal-body">
                <p><?= t('admin.files.modal.import.message', ['filename' => '<span id="import-filename"></span>']) ?></p>
                <p class="help-text"><?= t('admin.files.modal.import.help') ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel" data-action="cancel"><?= t('common.cancel') ?></button>
                <button class="modal-btn success" data-action="confirm"><?= t('admin.files.action.import') ?></button>
            </div>
        </div>
    </div>

    <!-- Delete File Modal -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal">
            <button class="modal-close" data-action="cancel">&times;</button>
            <div class="modal-header danger">
                <div class="modal-icon">🗑️</div>
                <h3 class="modal-title"><?= t('admin.files.modal.delete.title') ?></h3>
            </div>
            <div class="modal-body">
                <p><?= t('admin.files.modal.delete.message', ['filename' => '<span id="delete-filename"></span>']) ?></p>
                <p class="help-text"><?= t('admin.files.modal.delete.help') ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel" data-action="cancel"><?= t('common.cancel') ?></button>
                <button class="modal-btn danger" data-action="confirm"><?= t('admin.files.action.delete') ?></button>
            </div>
        </div>
    </div>

    <!-- Delete Orphaned File Modal -->
    <div id="delete-orphaned-modal" class="modal-overlay">
        <div class="modal">
            <button class="modal-close" data-action="cancel">&times;</button>
            <div class="modal-header danger">
                <div class="modal-icon">🗑️</div>
                <h3 class="modal-title"><?= t('admin.files.modal.delete_orphaned.title') ?></h3>
            </div>
            <div class="modal-body">
                <p><?= t('admin.files.modal.delete_orphaned.message', ['filename' => '<span id="delete-orphaned-filename"></span>']) ?></p>
                <p class="help-text"><?= t('admin.files.modal.delete_orphaned.help') ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel" data-action="cancel"><?= t('common.cancel') ?></button>
                <button class="modal-btn danger" data-action="confirm"><?= t('admin.files.action.delete') ?></button>
            </div>
        </div>
    </div>

    <!-- Rename File Modal -->
    <div id="rename-modal" class="modal-overlay">
        <div class="modal">
            <button class="modal-close" data-action="cancel">&times;</button>
            <div class="modal-header success">
                <div class="modal-icon">✏️</div>
                <h3 class="modal-title"><?= t('admin.files.modal.rename.title') ?></h3>
            </div>
            <div class="modal-body">
                <p><?= t('admin.files.modal.rename.prompt') ?></p>
                <input type="text" id="rename-input" class="modal-input" placeholder="<?= t('admin.files.modal.rename.placeholder') ?>">
                <p class="help-text"><?= t('admin.files.modal.rename.help') ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel" data-action="cancel"><?= t('common.cancel') ?></button>
                <button class="modal-btn success" data-action="confirm"><?= t('common.save') ?></button>
            </div>
        </div>
    </div>
</body>
</html>
