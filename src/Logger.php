<?php
/**
 * Logger - Aktivitetsloggning
 *
 * Loggar användaraktivitet till databasen för
 * säkerhetsövervakning och felsökning.
 *
 * Databasstruktur (logs-tabell):
 * - id: INT AUTO_INCREMENT
 * - user_id: INT NULL (FK till users)
 * - action: VARCHAR(255)
 * - ip_address: VARCHAR(45)
 * - created_at: DATETIME
 */

class Logger
{
    private static ?Logger $instance = null;
    private Database $db;

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
     * @return bool True om loggningen lyckades
     */
    public function log(string $action, ?int $userId = null, string $details = ''): bool
    {
        $ipAddress = $this->getClientIp();

        // Kombinera action och details
        $fullAction = $details ? "{$action}: {$details}" : $action;

        // Begränsa längden
        if (strlen($fullAction) > 255) {
            $fullAction = substr($fullAction, 0, 252) . '...';
        }

        try {
            $this->db->insert('logs', [
                'user_id' => $userId,
                'action' => $fullAction,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return true;

        } catch (Exception $e) {
            // Logga till fil om databasen misslyckas
            error_log("Logger DB-fel: {$action} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Logga för inloggad användare automatiskt
     *
     * @param string $action Åtgärdstyp
     * @param string $details Extra information
     * @return bool True om loggningen lyckades
     */
    public function logForCurrentUser(string $action, string $details = ''): bool
    {
        $userId = Session::getUserId();
        return $this->log($action, $userId, $details);
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
     * Rensa gamla loggar (kör via cron)
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
