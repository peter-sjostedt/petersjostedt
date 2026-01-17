<?php
/**
 * Backup - Databasbackup och återställning
 *
 * Hanterar skapande, rotation och återställning av databasbackuper.
 * Använder mysqldump för export och gzip för komprimering.
 */

class Backup
{
    private static ?self $instance = null;
    private Database $db;
    private Logger $logger;
    private string $backupDir;
    private array $config;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->backupDir = __DIR__ . '/../backups';

        // Backup-konfiguration
        $this->config = [
            'daily_keep' => 7,      // Behåll 7 dagliga backuper
            'weekly_keep' => 4,     // Behåll 4 veckovisa backuper
            'monthly_keep' => 12,   // Behåll 12 månatliga backuper
        ];

        // Skapa backup-mappar om de inte finns
        $this->initializeDirectories();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Skapa backup-mappstruktur
     */
    private function initializeDirectories(): void
    {
        $dirs = [
            $this->backupDir,
            $this->backupDir . '/daily',
            $this->backupDir . '/weekly',
            $this->backupDir . '/monthly',
            $this->backupDir . '/pre_restore',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Skapa .htaccess för att blockera HTTP-åtkomst
        $htaccess = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n");
        }
    }

    /**
     * Hitta mysqldump-binär
     */
    private function getMysqldumpPath(): string
    {
        return $this->findMysqlBinary('mysqldump');
    }

    /**
     * Hitta mysql-binär
     */
    private function getMysqlPath(): string
    {
        return $this->findMysqlBinary('mysql');
    }

    /**
     * Hitta MySQL-binär (mysqldump eller mysql)
     */
    private function findMysqlBinary(string $binary): string
    {
        // Testa om binären finns i PATH
        $output = [];
        $returnCode = 0;

        if (PHP_OS_FAMILY === 'Windows') {
            exec("where {$binary} 2>NUL", $output, $returnCode);
        } else {
            exec("which {$binary} 2>/dev/null", $output, $returnCode);
        }

        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        // Laragon-specifik sökning (Windows)
        if (PHP_OS_FAMILY === 'Windows') {
            $binaryExe = $binary . '.exe';
            $laragonPaths = [
                "C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/{$binaryExe}",
                "C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/{$binaryExe}",
                "C:/laragon/bin/mysql/mariadb-10.4.10-winx64/bin/{$binaryExe}",
            ];

            foreach ($laragonPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Sök i Laragon bin-mappen
            $laragonBase = 'C:/laragon/bin/mysql';
            if (is_dir($laragonBase)) {
                $dirs = glob($laragonBase . "/*/bin/{$binaryExe}");
                if (!empty($dirs)) {
                    return $dirs[0];
                }
            }
        }

        // Fallback till bara binärnamnet och hoppas det finns i PATH
        return $binary;
    }

    /**
     * Skapa databasbackup
     *
     * @param string $type Typ av backup: 'daily', 'weekly', 'monthly'
     * @return array ['success' => bool, 'filename' => string|null, 'size' => int|null, 'error' => string|null]
     */
    public function createBackup(string $type = 'daily'): array
    {
        $startTime = microtime(true);

        try {
            // Generera filnamn
            $timestamp = date('Y-m-d_His');
            $filename = "backup_{$type}_{$timestamp}.sql";
            $gzFilename = "{$filename}.gz";
            $tempPath = $this->backupDir . '/' . $filename;
            $gzPath = $this->backupDir . "/{$type}/{$gzFilename}";

            // Hämta databaskonfiguration
            $dbConfig = $this->getDatabaseConfig();

            // Hitta mysqldump
            $mysqldump = $this->getMysqldumpPath();

            // Bygg mysqldump-kommando
            $command = sprintf(
                '"%s" --user=%s --password=%s --host=%s %s > %s 2>&1',
                $mysqldump,
                escapeshellarg($dbConfig['user']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($tempPath)
            );

            // Kör mysqldump
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('mysqldump misslyckades: ' . implode("\n", $output));
            }

            // Verifiera att filen skapades och har innehåll
            if (!file_exists($tempPath) || filesize($tempPath) < 100) {
                throw new Exception('Backup-filen är tom eller för liten');
            }

            // Komprimera med gzip
            $sqlContent = file_get_contents($tempPath);
            $gzContent = gzencode($sqlContent, 9);

            if ($gzContent === false) {
                throw new Exception('Gzip-komprimering misslyckades');
            }

            file_put_contents($gzPath, $gzContent);

            // Radera okomprimerad fil
            unlink($tempPath);

            // Verifiera komprimerad fil
            $filesize = filesize($gzPath);
            if ($filesize < 50) {
                throw new Exception('Komprimerad backup är för liten');
            }

            $duration = round(microtime(true) - $startTime, 2);

            // Logga framgång
            $this->logger->info(
                'BACKUP_CREATED',
                Session::getUserId(),
                "Typ: {$type}, Fil: {$gzFilename}, Storlek: " . $this->formatBytes($filesize) . ", Tid: {$duration}s"
            );

            return [
                'success' => true,
                'filename' => $gzFilename,
                'path' => $gzPath,
                'size' => $filesize,
                'size_human' => $this->formatBytes($filesize),
                'duration' => $duration,
                'error' => null
            ];

        } catch (Exception $e) {
            $this->logger->error(
                'BACKUP_FAILED',
                Session::getUserId(),
                $e->getMessage()
            );

            return [
                'success' => false,
                'filename' => null,
                'size' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rotera backuper enligt retention policy
     *
     * @return array ['promoted' => int, 'deleted' => int, 'errors' => array]
     */
    public function rotateBackups(): array
    {
        $stats = [
            'promoted' => 0,
            'deleted' => 0,
            'errors' => []
        ];

        try {
            // Flytta dagliga backuper till veckovisa (varje söndag)
            if (date('w') == 0) { // Söndag
                $latestDaily = $this->getLatestBackup('daily');
                if ($latestDaily) {
                    $newName = 'backup_weekly_' . date('Y-m-d_His') . '.sql.gz';
                    $newPath = $this->backupDir . '/weekly/' . $newName;

                    if (copy($latestDaily['path'], $newPath)) {
                        $stats['promoted']++;
                    }
                }
            }

            // Flytta veckovisa backuper till månatliga (första dagen i månaden)
            if (date('d') == '01') { // Första dagen
                $latestWeekly = $this->getLatestBackup('weekly');
                if ($latestWeekly) {
                    $newName = 'backup_monthly_' . date('Y-m-d_His') . '.sql.gz';
                    $newPath = $this->backupDir . '/monthly/' . $newName;

                    if (copy($latestWeekly['path'], $newPath)) {
                        $stats['promoted']++;
                    }
                }
            }

            // Radera gamla backuper enligt retention policy
            $stats['deleted'] += $this->cleanOldBackups('daily', $this->config['daily_keep']);
            $stats['deleted'] += $this->cleanOldBackups('weekly', $this->config['weekly_keep']);
            $stats['deleted'] += $this->cleanOldBackups('monthly', $this->config['monthly_keep']);

        } catch (Exception $e) {
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Radera gamla backuper
     *
     * @param string $type Typ: 'daily', 'weekly', 'monthly'
     * @param int $keepCount Antal att behålla
     * @return int Antal raderade filer
     */
    private function cleanOldBackups(string $type, int $keepCount): int
    {
        $dir = $this->backupDir . '/' . $type;
        $backups = $this->listBackups($type);

        // Sortera efter datum (nyast först)
        usort($backups, fn($a, $b) => $b['modified'] <=> $a['modified']);

        $deleted = 0;
        $toDelete = array_slice($backups, $keepCount);

        foreach ($toDelete as $backup) {
            if (unlink($backup['path'])) {
                $deleted++;
                $this->logger->info(
                    'BACKUP_DELETED',
                    null,
                    "Typ: {$type}, Fil: {$backup['name']}"
                );
            }
        }

        return $deleted;
    }

    /**
     * Lista backuper av en viss typ
     *
     * @param string $type Typ: 'daily', 'weekly', 'monthly', eller 'all'
     * @return array Lista med backup-information
     */
    public function listBackups(string $type = 'all'): array
    {
        $backups = [];

        $types = $type === 'all' ? ['daily', 'weekly', 'monthly', 'pre_restore'] : [$type];

        foreach ($types as $t) {
            $dir = $this->backupDir . '/' . $t;

            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.sql.gz');

            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'type' => $t,
                    'path' => $file,
                    'size' => filesize($file),
                    'size_human' => $this->formatBytes(filesize($file)),
                    'modified' => filemtime($file),
                    'modified_human' => date('Y-m-d H:i:s', filemtime($file)),
                    'age_days' => floor((time() - filemtime($file)) / 86400)
                ];
            }
        }

        // Sortera efter datum (nyast först)
        usort($backups, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return $backups;
    }

    /**
     * Hämta senaste backup av en viss typ
     */
    private function getLatestBackup(string $type): ?array
    {
        $backups = $this->listBackups($type);
        return $backups[0] ?? null;
    }

    /**
     * Återställ databas från backup
     *
     * @param string $backupPath Sökväg till backup-fil
     * @return array ['success' => bool, 'error' => string|null, 'pre_restore_backup' => string|null]
     */
    public function restoreBackup(string $backupPath): array
    {
        $startTime = microtime(true);
        $preRestoreBackup = null;

        try {
            // Verifiera att filen finns
            if (!file_exists($backupPath)) {
                throw new Exception('Backup-filen hittades inte');
            }

            // Skapa automatisk backup före återställning
            $preBackupResult = $this->createBackup('pre_restore');
            if ($preBackupResult['success']) {
                $preRestoreBackup = $preBackupResult['filename'];
                $this->logger->info(
                    'BACKUP_PRE_RESTORE',
                    Session::getUserId(),
                    "Automatisk backup före återställning: {$preRestoreBackup}"
                );
            }

            // Dekomprimera
            $gzContent = file_get_contents($backupPath);
            $sqlContent = gzdecode($gzContent);

            if ($sqlContent === false) {
                throw new Exception('Kunde inte dekomprimera backup');
            }

            // Spara temporär SQL-fil
            $tempFile = $this->backupDir . '/temp_restore.sql';
            file_put_contents($tempFile, $sqlContent);

            // Hämta databaskonfiguration
            $dbConfig = $this->getDatabaseConfig();

            // Hitta mysql
            $mysql = $this->getMysqlPath();

            // Bygg mysql-kommando för import
            $command = sprintf(
                '"%s" --user=%s --password=%s --host=%s %s < %s 2>&1',
                $mysql,
                escapeshellarg($dbConfig['user']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($tempFile)
            );

            // Kör import
            exec($command, $output, $returnCode);

            // Radera temporär fil
            unlink($tempFile);

            if ($returnCode !== 0) {
                throw new Exception('MySQL import misslyckades: ' . implode("\n", $output));
            }

            $duration = round(microtime(true) - $startTime, 2);

            $this->logger->security(
                'BACKUP_RESTORED',
                Session::getUserId(),
                "Fil: " . basename($backupPath) . ", Tid: {$duration}s"
            );

            return [
                'success' => true,
                'duration' => $duration,
                'error' => null,
                'pre_restore_backup' => $preRestoreBackup
            ];

        } catch (Exception $e) {
            $this->logger->error(
                'BACKUP_RESTORE_FAILED',
                Session::getUserId(),
                $e->getMessage()
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'pre_restore_backup' => $preRestoreBackup
            ];
        }
    }

    /**
     * Verifiera backup-fil
     *
     * @param string $backupPath Sökväg till backup-fil
     * @return array ['valid' => bool, 'error' => string|null, 'info' => array]
     */
    public function verifyBackup(string $backupPath): array
    {
        try {
            if (!file_exists($backupPath)) {
                throw new Exception('Filen finns inte');
            }

            $filesize = filesize($backupPath);
            if ($filesize < 50) {
                throw new Exception('Filen är för liten');
            }

            // Försök dekomprimera
            $gzContent = file_get_contents($backupPath);
            $sqlContent = gzdecode($gzContent);

            if ($sqlContent === false) {
                throw new Exception('Kunde inte dekomprimera filen');
            }

            // Kontrollera att det ser ut som en SQL-dump
            if (!preg_match('/CREATE TABLE|INSERT INTO|DROP TABLE/i', $sqlContent)) {
                throw new Exception('Filen verkar inte innehålla giltig SQL');
            }

            // Räkna tabeller och rader
            preg_match_all('/CREATE TABLE/i', $sqlContent, $tables);
            preg_match_all('/INSERT INTO/i', $sqlContent, $inserts);

            return [
                'valid' => true,
                'error' => null,
                'info' => [
                    'size' => $filesize,
                    'size_human' => $this->formatBytes($filesize),
                    'uncompressed_size' => strlen($sqlContent),
                    'uncompressed_size_human' => $this->formatBytes(strlen($sqlContent)),
                    'compression_ratio' => round((1 - $filesize / strlen($sqlContent)) * 100, 1) . '%',
                    'tables' => count($tables[0]),
                    'inserts' => count($inserts[0])
                ]
            ];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'info' => []
            ];
        }
    }

    /**
     * Radera backup-fil
     *
     * @param string $backupPath Sökväg till backup-fil
     * @return bool
     */
    public function deleteBackup(string $backupPath): bool
    {
        if (!file_exists($backupPath)) {
            return false;
        }

        // Säkerhetskontroll: Filen måste vara i backup-mappen
        if (strpos(realpath($backupPath), realpath($this->backupDir)) !== 0) {
            $this->logger->security(
                'BACKUP_DELETE_BLOCKED',
                Session::getUserId(),
                "Försök att radera fil utanför backup-mapp: {$backupPath}"
            );
            return false;
        }

        $filename = basename($backupPath);

        if (unlink($backupPath)) {
            $this->logger->info(
                'BACKUP_DELETED',
                Session::getUserId(),
                "Fil: {$filename}"
            );
            return true;
        }

        return false;
    }

    /**
     * Hämta databaskonfiguration från config-fil
     */
    private function getDatabaseConfig(): array
    {
        $configFile = __DIR__ . '/../config/database.php';

        if (!file_exists($configFile)) {
            throw new Exception('Databaskonfiguration saknas');
        }

        $config = require $configFile;

        return [
            'host' => $config['host'] ?? 'localhost',
            'database' => $config['name'] ?? $config['database'] ?? '',
            'user' => $config['user'] ?? $config['username'] ?? '',
            'password' => $config['pass'] ?? $config['password'] ?? ''
        ];
    }

    /**
     * Formatera bytes till läsbar storlek
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Hämta backup-statistik
     */
    public function getStats(): array
    {
        $backups = $this->listBackups('all');

        $stats = [
            'total' => count($backups),
            'daily' => count(array_filter($backups, fn($b) => $b['type'] === 'daily')),
            'weekly' => count(array_filter($backups, fn($b) => $b['type'] === 'weekly')),
            'monthly' => count(array_filter($backups, fn($b) => $b['type'] === 'monthly')),
            'pre_restore' => count(array_filter($backups, fn($b) => $b['type'] === 'pre_restore')),
            'total_size' => array_sum(array_column($backups, 'size')),
            'oldest' => null,
            'newest' => null,
        ];

        if (!empty($backups)) {
            $stats['total_size_human'] = $this->formatBytes($stats['total_size']);
            $stats['oldest'] = end($backups)['modified_human'];
            $stats['newest'] = $backups[0]['modified_human'];
        }

        return $stats;
    }
}
