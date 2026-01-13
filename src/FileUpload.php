<?php
/**
 * FileUpload - Generell filuppladdning
 *
 * Hanterar uppladdning av filer med validering och säker lagring.
 */

class FileUpload
{
    private static ?FileUpload $instance = null;
    private Database $db;
    private Logger $logger;
    private array $config;
    private ?string $error = null;

    /**
     * Privat konstruktor - använd getInstance()
     */
    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();

        $appConfig = require __DIR__ . '/../config/app.php';
        $this->config = $appConfig['upload'];

        // Skapa upload-mapp om den inte finns
        if (!is_dir($this->config['base_path'])) {
            mkdir($this->config['base_path'], 0755, true);
        }
    }

    /**
     * Hämta instansen (singleton)
     */
    public static function getInstance(): FileUpload
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Ladda upp fil
     *
     * @param array $file $_FILES['inputname']
     * @param string $folder Undermapp (t.ex. 'documents', 'misc')
     * @return array|false Array med id, stored_name, file_path eller false vid fel
     */
    public function upload(array $file, string $folder = ''): array|false
    {
        $this->error = null;

        // Kontrollera att fil laddades upp
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->error = t('upload.error_failed');
            return false;
        }

        // Kontrollera uppladdningsfel
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // Validera filstorlek
        if ($file['size'] > $this->config['max_file_size']) {
            $this->error = t('upload.error_size');
            return false;
        }

        // Validera MIME-typ med finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->config['allowed_types'])) {
            $this->error = t('upload.error_type');
            return false;
        }

        // Generera säkert filnamn
        $extension = $this->getExtensionFromMime($mimeType);
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

        // Skapa målmapp
        $targetDir = $this->config['base_path'];
        if ($folder) {
            $folder = $this->sanitizeFolder($folder);
            $targetDir .= '/' . $folder;
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $targetPath = $targetDir . '/' . $storedName;
        $relativePath = $folder ? $folder . '/' . $storedName : $storedName;

        // Flytta fil
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->error = t('upload.error_failed');
            return false;
        }

        // Spara metadata i databas
        $userId = Session::isLoggedIn() ? Session::getUserId() : null;

        $fileId = $this->db->insert('files', [
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $file['size'],
            'file_path' => $relativePath,
            'uploaded_by' => $userId
        ]);

        // Logga uppladdning
        $this->logger->info('FILE_UPLOADED', $userId, "Fil uppladdad: {$file['name']} ({$mimeType}, " . $this->formatSize($file['size']) . ")");

        return [
            'id' => $fileId,
            'stored_name' => $storedName,
            'file_path' => $relativePath,
            'original_name' => $file['name'],
            'mime_type' => $mimeType,
            'file_size' => $file['size']
        ];
    }

    /**
     * Radera fil
     *
     * @param int $fileId
     * @return bool
     */
    public function delete(int $fileId): bool
    {
        $file = self::getById($fileId);

        if (!$file) {
            $this->error = 'Fil hittades inte';
            return false;
        }

        $fullPath = $this->config['base_path'] . '/' . $file['file_path'];

        // Radera fil från disk
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Radera från databas
        $this->db->delete('files', 'id = ?', [$fileId]);

        // Logga radering
        $userId = Session::isLoggedIn() ? Session::getUserId() : null;
        $this->logger->info('FILE_DELETED', $userId, "Fil raderad: {$file['original_name']} (ID: {$fileId})");

        return true;
    }

    /**
     * Hämta fil från databas
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        $db = Database::getInstance();
        $file = $db->fetchOne('SELECT * FROM files WHERE id = ?', [$id]);
        return $file ?: null;
    }

    /**
     * Hämta alla filer i en mapp
     *
     * @param string $folder
     * @return array
     */
    public static function getByFolder(string $folder = ''): array
    {
        $db = Database::getInstance();

        if ($folder) {
            $folder = rtrim($folder, '/');
            return $db->fetchAll(
                'SELECT * FROM files WHERE file_path LIKE ? ORDER BY created_at DESC',
                [$folder . '/%']
            );
        }

        return $db->fetchAll(
            'SELECT * FROM files WHERE file_path NOT LIKE ? ORDER BY created_at DESC',
            ['%/%']
        );
    }

    /**
     * Kontrollera om GD finns
     *
     * @return bool
     */
    public static function gdAvailable(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * Hämta senaste felmeddelande
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Sanera mappnamn
     */
    private function sanitizeFolder(string $folder): string
    {
        $folder = str_replace(['..', '/', '\\'], '', $folder);
        $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
        return $folder;
    }

    /**
     * Hämta filändelse från MIME-typ
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];

        return $map[$mimeType] ?? 'bin';
    }

    /**
     * Översätt uppladdningsfel
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => t('upload.error_size'),
            UPLOAD_ERR_PARTIAL => 'Filen laddades endast delvis upp',
            UPLOAD_ERR_NO_FILE => 'Ingen fil valdes',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporär mapp saknas',
            UPLOAD_ERR_CANT_WRITE => 'Kunde inte skriva till disk',
            UPLOAD_ERR_EXTENSION => 'Uppladdning stoppades av PHP-extension',
            default => t('upload.error_failed'),
        };
    }

    /**
     * Formatera filstorlek
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Förhindra kloning av singleton
     */
    private function __clone() {}

    /**
     * Förhindra unserialisering av singleton
     */
    public function __wakeup()
    {
        throw new Exception('Kan inte unserialisera singleton');
    }
}
