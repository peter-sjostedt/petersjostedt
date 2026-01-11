<?php
/**
 * Logger - Aktivitetsloggning
 *
 * Loggar användaraktivitet till databas OCH fil för
 * säkerhetsövervakning och felsökning.
 *
 * Databasstruktur (logs-tabell):
 * - id: INT AUTO_INCREMENT
 * - user_id: INT NULL (FK till users)
 * - action: VARCHAR(255)
 * - ip_address: VARCHAR(45)
 * - created_at: DATETIME
 *
 * Filstruktur (logs/):
 * - app-YYYY-MM-DD.log - Applikationsloggar
 * - error-YYYY-MM-DD.log - Felloggar
 * - security-YYYY-MM-DD.log - Säkerhetsloggar
 * - debug-YYYY-MM-DD.log - Debugloggar (endast development)
 */

class Logger
{
    private static ?Logger $instance = null;
    private Database $db;
    private string $logDir;

    // Loggningsnivåer
    public const LEVEL_DEBUG = 'DEBUG';
    public const LEVEL_INFO = 'INFO';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_ERROR = 'ERROR';
    public const LEVEL_SECURITY = 'SECURITY';

    // Fördefinierade åtgärdstyper
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGIN_FAILED = 'LOGIN_FAILED';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_REGISTER = 'REGISTER';
    public const ACTION_PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    public const ACTION_PASSWORD_RESET = 'PASSWORD_RESET';
    public const ACTION_PROFILE_UPDATE = 'PROFILE_UPDATE';
    public const ACTION_SETTINGS_CHANGE = 'SETTINGS_CHANGE';
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_VIEW = 'VIEW';
    public const ACTION_EXPORT = 'EXPORT';
    public const ACTION_IMPORT = 'IMPORT';
    public const ACTION_SECURITY_ALERT = 'SECURITY_ALERT';
    public const ACTION_ERROR = 'ERROR';

    /**
     * Privat konstruktor - använd getInstance()
     */
    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->logDir = __DIR__ . '/../logs';

        // Skapa logs-mapp om den inte finns
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Hämta instansen (singleton)
     */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Logga en händelse
     *
     * @param string $action Åtgärdstyp (använd konstanter)
     * @param int|null $userId Användar-ID (null för anonyma)
     * @param string $details Extra information (valfritt)
     * @param string $level Loggningsnivå (default: INFO)
     * @return bool True om loggningen lyckades
     */
    public function log(string $action, ?int $userId = null, string $details = '', string $level = self::LEVEL_INFO): bool
    {
        $ipAddress = $this->getClientIp();

        // Kombinera action och details
        $fullAction = $details ? "{$action}: {$details}" : $action;

        // Begränsa längden för databas
        $dbAction = $fullAction;
        if (strlen($dbAction) > 255) {
            $dbAction = substr($dbAction, 0, 252) . '...';
        }

        // Logga till fil först (fungerar även om DB är nere)
        $this->logToFile($level, $action, $userId, $ipAddress, $fullAction);

        // Försök logga till databas
        try {
            $this->db->insert('logs', [
                'user_id' => $userId,
                'action' => $dbAction,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return true;

        } catch (Exception $e) {
            // Om databas misslyckas, logga till error-fil
            $this->logToFile(self::LEVEL_ERROR, 'DB_ERROR', null, $ipAddress,
                "Kunde inte logga till databas: {$action} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Logga för inloggad användare automatiskt
     *
     * @param string $action Åtgärdstyp
     * @param string $details Extra information
     * @param string $level Loggningsnivå
     * @return bool True om loggningen lyckades
     */
    public function logForCurrentUser(string $action, string $details = '', string $level = self::LEVEL_INFO): bool
    {
        $userId = Session::getUserId();
        return $this->log($action, $userId, $details, $level);
    }

    /**
     * Logga till fil
     *
     * @param string $level Loggningsnivå
     * @param string $action Åtgärdstyp
     * @param int|null $userId Användar-ID
     * @param string $ipAddress IP-adress
     * @param string $message Meddelande
     */
    private function logToFile(string $level, string $action, ?int $userId, string $ipAddress, string $message): void
    {
        // Skippa DEBUG-loggar i produktion
        if ($level === self::LEVEL_DEBUG && ENVIRONMENT === 'production') {
            return;
        }

        // Bestäm vilken fil som ska användas
        $filename = $this->getLogFilename($level);
        $filepath = $this->logDir . '/' . $filename;

        // Formatera loggmeddelandet
        $timestamp = date('Y-m-d H:i:s');
        $userStr = $userId ? "User:$userId" : 'Guest';
        $logLine = sprintf(
            "[%s] [%s] [%s] [IP:%s] %s: %s\n",
            $timestamp,
            $level,
            $userStr,
            $ipAddress,
            $action,
            $message
        );

        // Skriv till fil
        try {
            file_put_contents($filepath, $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Fallback till PHP:s error_log om filskrivning misslyckas
            error_log("Logger-filfel: " . $e->getMessage());
            error_log($logLine);
        }
    }

    /**
     * Hämta filnamn baserat på loggningsnivå
     *
     * @param string $level Loggningsnivå
     * @return string Filnamn
     */
    private function getLogFilename(string $level): string
    {
        $date = date('Y-m-d');

        switch ($level) {
            case self::LEVEL_ERROR:
                return "error-{$date}.log";
            case self::LEVEL_SECURITY:
                return "security-{$date}.log";
            case self::LEVEL_DEBUG:
                return "debug-{$date}.log";
            case self::LEVEL_WARNING:
            case self::LEVEL_INFO:
            default:
                return "app-{$date}.log";
        }
    }

    /**
     * Genvägsmetoder för olika loggningsnivåer
     */
    public function debug(string $action, ?int $userId = null, string $details = ''): bool
    {
        return $this->log($action, $userId, $details, self::LEVEL_DEBUG);
    }

    public function info(string $action, ?int $userId = null, string $details = ''): bool
    {
        return $this->log($action, $userId, $details, self::LEVEL_INFO);
    }

    public function warning(string $action, ?int $userId = null, string $details = ''): bool
    {
        return $this->log($action, $userId, $details, self::LEVEL_WARNING);
    }

    public function error(string $action, ?int $userId = null, string $details = ''): bool
    {
        return $this->log($action, $userId, $details, self::LEVEL_ERROR);
    }

    public function security(string $action, ?int $userId = null, string $details = ''): bool
    {
        return $this->log($action, $userId, $details, self::LEVEL_SECURITY);
    }

    /**
     * Hämta klientens IP-adress
     * Hanterar proxies och load balancers
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Vanlig proxy
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Direkt anslutning
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For kan innehålla flera IP:er, ta första
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);

                // Validera IP-adressen
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Hämta loggar för en specifik användare
     *
     * @param int $userId Användar-ID
     * @param int $limit Max antal (0 = alla)
     * @param int $offset Startposition
     * @return array Lista med loggposter
     */
    public function getByUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM logs WHERE user_id = ? ORDER BY created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
        }

        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Hämta loggar efter åtgärdstyp
     *
     * @param string $action Åtgärdstyp (eller del av)
     * @param int $limit Max antal
     * @return array Lista med loggposter
     */
    public function getByAction(string $action, int $limit = 100): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, u.email, u.name
             FROM logs l
             LEFT JOIN users u ON l.user_id = u.id
             WHERE l.action LIKE ?
             ORDER BY l.created_at DESC
             LIMIT ?",
            [$action . '%', $limit]
        );
    }

    /**
     * Hämta loggar inom ett datumintervall
     *
     * @param string $from Startdatum (Y-m-d)
     * @param string $to Slutdatum (Y-m-d)
     * @param int $limit Max antal
     * @return array Lista med loggposter
     */
    public function getByDateRange(string $from, string $to, int $limit = 1000): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, u.email, u.name
             FROM logs l
             LEFT JOIN users u ON l.user_id = u.id
             WHERE DATE(l.created_at) BETWEEN ? AND ?
             ORDER BY l.created_at DESC
             LIMIT ?",
            [$from, $to, $limit]
        );
    }

    /**
     * Hämta loggar för en specifik IP-adress
     *
     * @param string $ipAddress IP-adress
     * @param int $limit Max antal
     * @return array Lista med loggposter
     */
    public function getByIp(string $ipAddress, int $limit = 100): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, u.email, u.name
             FROM logs l
             LEFT JOIN users u ON l.user_id = u.id
             WHERE l.ip_address = ?
             ORDER BY l.created_at DESC
             LIMIT ?",
            [$ipAddress, $limit]
        );
    }

    /**
     * Hämta senaste loggarna
     *
     * @param int $limit Max antal
     * @return array Lista med loggposter
     */
    public function getRecent(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, u.email, u.name
             FROM logs l
             LEFT JOIN users u ON l.user_id = u.id
             ORDER BY l.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Hämta säkerhetsvarningar
     *
     * @param int $limit Max antal
     * @return array Lista med varningar
     */
    public function getSecurityAlerts(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, u.email, u.name
             FROM logs l
             LEFT JOIN users u ON l.user_id = u.id
             WHERE l.action LIKE 'SECURITY%' OR l.action LIKE 'LOGIN_FAILED%'
             ORDER BY l.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Räkna misslyckade inloggningsförsök för en IP
     *
     * @param string $ipAddress IP-adress
     * @param int $minutes Tidsperiod i minuter
     * @return int Antal misslyckade försök
     */
    public function countFailedLogins(string $ipAddress, int $minutes = 15): int
    {
        $result = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM logs
             WHERE ip_address = ?
             AND action LIKE 'LOGIN_FAILED%'
             AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$ipAddress, $minutes]
        );

        return (int) $result;
    }

    /**
     * Kontrollera om IP är blockerad (för många misslyckade försök)
     *
     * @param string $ipAddress IP-adress
     * @param int $maxAttempts Max antal försök
     * @param int $minutes Tidsperiod i minuter
     * @return bool True om blockerad
     */
    public function isIpBlocked(string $ipAddress, int $maxAttempts = 5, int $minutes = 15): bool
    {
        return $this->countFailedLogins($ipAddress, $minutes) >= $maxAttempts;
    }

    /**
     * Rensa gamla loggar från databas (kör via cron)
     *
     * @param int $daysToKeep Antal dagar att behålla
     * @return int Antal borttagna poster
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        try {
            return $this->db->execute(
                "DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$daysToKeep]
            );
        } catch (Exception $e) {
            error_log('Kunde inte rensa gamla loggar: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Rotera loggfiler (kör via cron)
     * Komprimerar gamla loggfiler och raderar mycket gamla
     *
     * @param int $daysToKeep Antal dagar att behålla okomprimerade loggar
     * @param int $daysToArchive Antal dagar att behålla komprimerade loggar
     * @return array Statistik över rotation
     */
    public function rotateLogFiles(int $daysToKeep = 7, int $daysToArchive = 90): array
    {
        $stats = [
            'compressed' => 0,
            'deleted' => 0,
            'errors' => []
        ];

        if (!is_dir($this->logDir)) {
            $stats['errors'][] = "Loggmapp saknas: {$this->logDir}";
            return $stats;
        }

        $files = glob($this->logDir . '/*.log');
        $cutoffDate = time() - ($daysToKeep * 86400);
        $archiveCutoff = time() - ($daysToArchive * 86400);

        foreach ($files as $file) {
            $fileTime = filemtime($file);
            $basename = basename($file);

            // Radera mycket gamla komprimerade filer
            if (str_ends_with($file, '.gz')) {
                if ($fileTime < $archiveCutoff) {
                    if (unlink($file)) {
                        $stats['deleted']++;
                    } else {
                        $stats['errors'][] = "Kunde inte radera: $basename";
                    }
                }
                continue;
            }

            // Komprimera gamla okomprimerade loggar
            if ($fileTime < $cutoffDate) {
                $gzFile = $file . '.gz';

                if (function_exists('gzopen')) {
                    $gz = gzopen($gzFile, 'wb9');
                    if ($gz) {
                        $content = file_get_contents($file);
                        gzwrite($gz, $content);
                        gzclose($gz);

                        // Radera originalet efter komprimering
                        if (unlink($file)) {
                            $stats['compressed']++;
                        } else {
                            $stats['errors'][] = "Kunde inte radera efter komprimering: $basename";
                        }
                    } else {
                        $stats['errors'][] = "Kunde inte komprimera: $basename";
                    }
                } else {
                    $stats['errors'][] = "gzip-stöd saknas, kan inte komprimera loggar";
                    break; // Hoppa över resten om gzip saknas
                }
            }
        }

        return $stats;
    }

    /**
     * Hämta loggfiler
     *
     * @param bool $includeArchived Inkludera komprimerade filer
     * @return array Lista med loggfiler och deras storlekar
     */
    public function getLogFiles(bool $includeArchived = false): array
    {
        $files = [];
        $pattern = $includeArchived ? '*.{log,gz}' : '*.log';
        $logFiles = glob($this->logDir . '/' . $pattern, GLOB_BRACE);

        foreach ($logFiles as $file) {
            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'size_human' => $this->formatBytes(filesize($file)),
                'modified' => filemtime($file),
                'modified_human' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        // Sortera efter senast modifierad
        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $files;
    }

    /**
     * Läs loggfil
     *
     * @param string $filename Filnamn (utan sökväg)
     * @param int $lines Antal rader att läsa (0 = alla)
     * @param bool $reverse Läs underifrån
     * @return array|false Logginnehåll eller false
     */
    public function readLogFile(string $filename, int $lines = 100, bool $reverse = true): array|false
    {
        $filepath = $this->logDir . '/' . basename($filename);

        if (!file_exists($filepath)) {
            return false;
        }

        // Hantera komprimerade filer
        if (str_ends_with($filepath, '.gz')) {
            $content = gzfile($filepath);
        } else {
            $content = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        if ($content === false) {
            return false;
        }

        // Begränsa antal rader
        if ($lines > 0) {
            $content = $reverse ? array_slice($content, -$lines) : array_slice($content, 0, $lines);
        }

        // Vänd ordning om reverse
        if ($reverse) {
            $content = array_reverse($content);
        }

        return $content;
    }

    /**
     * Formatera bytes till läsbar storlek
     *
     * @param int $bytes Antal bytes
     * @return string Formaterad storlek
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Hämta statistik över loggar
     *
     * @return array Statistik
     */
    public function getStats(): array
    {
        $stats = [];

        // Totalt antal loggar
        $stats['total'] = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM logs");

        // Loggar senaste 24h
        $stats['last_24h'] = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        // Misslyckade inloggningar senaste 24h
        $stats['failed_logins_24h'] = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM logs
             WHERE action LIKE 'LOGIN_FAILED%'
             AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        // Unika användare senaste 24h
        $stats['unique_users_24h'] = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT user_id) FROM logs
             WHERE user_id IS NOT NULL
             AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        // Unika IP-adresser senaste 24h
        $stats['unique_ips_24h'] = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT ip_address) FROM logs
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        return $stats;
    }

    /**
     * Statisk genväg för att logga
     */
    public static function write(string $action, ?int $userId = null, string $details = ''): bool
    {
        return self::getInstance()->log($action, $userId, $details);
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
