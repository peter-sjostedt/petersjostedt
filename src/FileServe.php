<?php
/**
 * FileServe - Säker filservering
 *
 * Serverar filer via PHP med behörighetskontroll och korrekta headers.
 */

class FileServe
{
    private static Logger $logger;

    /**
     * Servera fil
     *
     * @param int|null $fileId
     * @return void
     */
    public static function serve(?int $fileId): void
    {
        self::$logger = Logger::getInstance();

        // Kontrollera att ID angivits
        if (!$fileId) {
            self::notFound();
            return;
        }

        // Hämta filinfo från databas
        $file = FileUpload::getById($fileId);

        if (!$file) {
            self::notFound();
            return;
        }

        // Kontrollera att filen finns på disk
        $appConfig = require __DIR__ . '/../config/app.php';
        $fullPath = $appConfig['upload']['base_path'] . '/' . $file['file_path'];

        if (!file_exists($fullPath)) {
            self::$logger->error('FILE_SERVE_MISSING', null, "Fil saknas på disk: {$file['file_path']} (ID: {$fileId})");
            self::notFound();
            return;
        }

        // Logga filåtkomst
        $userId = Session::isLoggedIn() ? Session::getUserId() : null;
        self::$logger->debug('FILE_SERVED', $userId, "Fil serverad: {$file['original_name']} (ID: {$fileId})");

        // Sätt headers
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . $file['file_size']);
        header('Content-Disposition: inline; filename="' . self::sanitizeFilename($file['original_name']) . '"');
        header('Cache-Control: public, max-age=31536000'); // 1 år
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

        // Servera filen
        readfile($fullPath);
        exit;
    }

    /**
     * Servera fil som nedladdning
     *
     * @param int|null $fileId
     * @return void
     */
    public static function download(?int $fileId): void
    {
        self::$logger = Logger::getInstance();

        if (!$fileId) {
            self::notFound();
            return;
        }

        $file = FileUpload::getById($fileId);

        if (!$file) {
            self::notFound();
            return;
        }

        $appConfig = require __DIR__ . '/../config/app.php';
        $fullPath = $appConfig['upload']['base_path'] . '/' . $file['file_path'];

        if (!file_exists($fullPath)) {
            self::notFound();
            return;
        }

        // Logga nedladdning
        $userId = Session::isLoggedIn() ? Session::getUserId() : null;
        self::$logger->info('FILE_DOWNLOADED', $userId, "Fil nedladdad: {$file['original_name']} (ID: {$fileId})");

        // Sätt headers för nedladdning
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . $file['file_size']);
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($file['original_name']) . '"');
        header('Cache-Control: no-cache, must-revalidate');

        readfile($fullPath);
        exit;
    }

    /**
     * Sanera filnamn för Content-Disposition
     */
    private static function sanitizeFilename(string $filename): string
    {
        // Ta bort farliga tecken
        $filename = str_replace(['"', '\\', '/', ':', '*', '?', '<', '>', '|'], '', $filename);
        return $filename;
    }

    /**
     * Visa 404
     */
    private static function notFound(): void
    {
        http_response_code(404);
        echo 'Filen hittades inte';
        exit;
    }
}
