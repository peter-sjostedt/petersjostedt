<?php
/**
 * ImageUpload - Bildhantering med resize och crop
 *
 * Hanterar uppladdning av bilder med automatisk storleksanpassning.
 */

class ImageUpload
{
    private static ?ImageUpload $instance = null;
    private Database $db;
    private Logger $logger;
    private FileUpload $fileUpload;
    private array $uploadConfig;
    private array $imageConfig;
    private ?string $error = null;

    /**
     * Privat konstruktor - använd getInstance()
     */
    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->fileUpload = FileUpload::getInstance();

        $appConfig = require __DIR__ . '/../config/app.php';
        $this->uploadConfig = $appConfig['upload'];
        $this->imageConfig = $appConfig['images'];
    }

    /**
     * Hämta instansen (singleton)
     */
    public static function getInstance(): ImageUpload
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Ladda upp och processa bild - skapar alla tre storlekar automatiskt
     *
     * @param array $file $_FILES['inputname']
     * @param string $folder Undermapp (t.ex. 'images', 'avatars')
     * @return array|false Array med thumbnail_id, medium_id, large_id
     */
    public function upload(array $file, string $folder = 'images'): array|false
    {
        $this->error = null;

        // Om GD inte finns, fallback till FileUpload
        if (!FileUpload::gdAvailable()) {
            $this->logger->warning('IMAGE_UPLOAD_NO_GD', null, 'GD saknas, sparar original utan resize');
            $result = $this->fileUpload->upload($file, $folder);

            if ($result) {
                $result['resized'] = false;
                $result['message'] = t('upload.no_gd');
            }

            return $result;
        }

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

        // Validera MIME-typ
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedImageTypes)) {
            $this->error = t('upload.error_type');
            return false;
        }

        // Ladda bilden
        $sourceImage = $this->loadImage($file['tmp_name'], $mimeType);
        if (!$sourceImage) {
            $this->error = 'Kunde inte ladda bilden';
            return false;
        }

        // Fixa orientering från EXIF
        $sourceImage = $this->fixOrientation($file['tmp_name'], $sourceImage, $mimeType);

        // Hämta originalstorlek
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        $originalSize = $file['size'];

        // Spara original om konfigurerat
        if ($this->imageConfig['keep_original']) {
            $this->saveOriginal($file, $folder);
        }

        // Skapa alla tre storlekar
        $results = [];
        $userId = Session::isLoggedIn() ? Session::getUserId() : null;
        $parentId = null;

        foreach (['thumbnail', 'medium', 'large'] as $sizeName) {
            $sizeConfig = $this->imageConfig['sizes'][$sizeName];

            // Processa bilden
            $processedImage = $this->processImage(
                $sourceImage,
                $originalWidth,
                $originalHeight,
                $sizeConfig
            );

            // Generera filnamn
            $extension = ($mimeType === 'image/png') ? 'png' : 'jpg';
            $storedName = bin2hex(random_bytes(16)) . '_' . $sizeName . '.' . $extension;

            // Skapa målmapp
            $targetDir = $this->uploadConfig['base_path'];
            if ($folder) {
                $folderSafe = $this->sanitizeFolder($folder);
                $targetDir .= '/' . $folderSafe;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
            }

            $targetPath = $targetDir . '/' . $storedName;
            $relativePath = $folder ? $this->sanitizeFolder($folder) . '/' . $storedName : $storedName;

            // Spara bilden
            if ($mimeType === 'image/png') {
                imagesavealpha($processedImage, true);
                imagepng($processedImage, $targetPath, $this->imageConfig['png_compression']);
            } else {
                imagejpeg($processedImage, $targetPath, $this->imageConfig['jpeg_quality']);
            }

            // Hämta ny filstorlek
            $newSize = filesize($targetPath);
            $finalMimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';

            // Spara metadata i databas
            $fileId = $this->db->insert('files', [
                'original_name' => $file['name'],
                'stored_name' => $storedName,
                'mime_type' => $finalMimeType,
                'file_size' => $newSize,
                'file_path' => $relativePath,
                'uploaded_by' => $userId,
                'parent_id' => $parentId,
                'size_variant' => $sizeName
            ]);

            // Första bilden (thumbnail) blir parent
            if ($parentId === null) {
                $parentId = $fileId;
                // Uppdatera parent_id för thumbnail till sig själv
                $this->db->execute('UPDATE files SET parent_id = ? WHERE id = ?', [$fileId, $fileId]);
            }

            $results[$sizeName . '_id'] = $fileId;
            $results[$sizeName] = [
                'id' => $fileId,
                'stored_name' => $storedName,
                'file_path' => $relativePath,
                'file_size' => $newSize,
                'width' => imagesx($processedImage),
                'height' => imagesy($processedImage)
            ];

            // Frigör minne
            imagedestroy($processedImage);
        }

        // Frigör källbild
        imagedestroy($sourceImage);

        // Logga uppladdning
        $this->logger->info('IMAGE_UPLOADED', $userId,
            "Bild uppladdad: {$file['name']} - Skapade 3 varianter (thumb, medium, large)"
        );

        // Returnera alla varianter
        $results['original_name'] = $file['name'];
        $results['original_width'] = $originalWidth;
        $results['original_height'] = $originalHeight;
        $results['original_size'] = $originalSize;
        $results['resized'] = true;
        $results['parent_id'] = $parentId;

        return $results;
    }

    /**
     * Ladda bild från fil
     */
    private function loadImage(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * Fixa bildorientering från EXIF
     */
    private function fixOrientation(string $path, $image, string $mimeType)
    {
        if ($mimeType !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        if (!$exif || !isset($exif['Orientation'])) {
            return $image;
        }

        $orientation = $exif['Orientation'];

        switch ($orientation) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        return $image;
    }

    /**
     * Processa bild (resize/crop)
     */
    private function processImage($sourceImage, int $width, int $height, array $sizeConfig)
    {
        $targetWidth = $sizeConfig['width'];
        $targetHeight = $sizeConfig['height'];
        $crop = $sizeConfig['crop'];

        if ($crop) {
            // Crop till exakt storlek
            $ratio = max($targetWidth / $width, $targetHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
            $this->preserveTransparency($tmpImage);

            imagecopyresampled($tmpImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            $finalImage = imagecreatetruecolor($targetWidth, $targetHeight);
            $this->preserveTransparency($finalImage);

            $cropX = (int)(($newWidth - $targetWidth) / 2);
            $cropY = (int)(($newHeight - $targetHeight) / 2);

            imagecopy($finalImage, $tmpImage, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight);
            imagedestroy($tmpImage);

            return $finalImage;
        } else {
            // Resize med bibehållen proportion
            $ratio = min($targetWidth / $width, $targetHeight / $height);

            // Zooma inte upp mindre bilder
            if ($ratio > 1) {
                $ratio = 1;
            }

            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            $this->preserveTransparency($newImage);

            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            return $newImage;
        }
    }

    /**
     * Bevara transparens för PNG/GIF
     */
    private function preserveTransparency($image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }

    /**
     * Spara original i separat mapp
     */
    private function saveOriginal(array $file, string $folder): void
    {
        $originalDir = $this->uploadConfig['base_path'] . '/originals';
        if ($folder) {
            $originalDir .= '/' . $this->sanitizeFolder($folder);
        }

        if (!is_dir($originalDir)) {
            mkdir($originalDir, 0755, true);
        }

        $originalName = bin2hex(random_bytes(16)) . '_original_' . $file['name'];
        copy($file['tmp_name'], $originalDir . '/' . $originalName);
    }

    /**
     * Hämta senaste felmeddelande
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
