<?php
/**
 * File Picker API - Endpoint för filväljaren
 *
 * Endpoints:
 * - GET ?folder=xxx&type=all|image|document - Hämta filer och mappar
 * - GET ?id=xxx - Hämta specifik fil
 */

require_once __DIR__ . '/includes/config.php';
secure_session_start();
set_security_headers();

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    // Hämta specifik fil
    if (isset($_GET['id'])) {
        $fileId = (int)$_GET['id'];

        $file = $db->fetchOne('SELECT * FROM files WHERE id = ?', [$fileId]);

        if (!$file) {
            echo json_encode(['success' => false, 'error' => 'Fil hittades inte']);
            exit;
        }

        // Lägg till URL
        $file['url'] = 'serve.php?id=' . $file['id'];

        echo json_encode(['success' => true, 'file' => $file]);
        exit;
    }

    // Hämta filer och mappar
    $folder = $_GET['folder'] ?? '';
    $type = $_GET['type'] ?? 'all';

    // Sanera mappnamn
    $folder = str_replace(['..', '/', '\\'], '', $folder);

    // Bygg query
    $conditions = [];
    $params = [];

    // Filtrera på mapp
    if ($folder) {
        $conditions[] = "file_path LIKE ?";
        $params[] = $folder . '/%';
    } else {
        // Endast filer i root (ingen / i file_path)
        $conditions[] = "file_path NOT LIKE '%/%'";
    }

    // Filtrera på filtyp
    switch ($type) {
        case 'image':
            $conditions[] = "mime_type LIKE 'image/%'";
            break;
        case 'document':
            $conditions[] = "(mime_type = 'application/pdf' OR mime_type LIKE 'text/%')";
            break;
        // 'all' - ingen extra filtrering
    }

    $whereClause = implode(' AND ', $conditions);

    $files = $db->fetchAll(
        "SELECT * FROM files WHERE $whereClause ORDER BY created_at DESC",
        $params
    );

    // Lägg till URL för varje fil
    foreach ($files as &$file) {
        $file['url'] = 'serve.php?id=' . $file['id'];
    }

    // Hämta mappar (endast i root)
    $folders = [];
    if (!$folder) {
        $appConfig = require __DIR__ . '/../config/app.php';
        $basePath = $appConfig['upload']['base_path'];

        if (is_dir($basePath)) {
            $dirs = scandir($basePath);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($basePath . '/' . $dir)) {
                    // Skippa 'originals' mappen
                    if ($dir !== 'originals') {
                        $folders[] = $dir;
                    }
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'files' => $files,
        'folders' => $folders,
        'current_folder' => $folder
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Serverfel: ' . $e->getMessage()
    ]);
}
