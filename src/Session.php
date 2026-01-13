<?php
/**
 * Session - Säker sessionshantering med databasstöd
 *
 * Funktioner:
 * - Säkra session-cookies (HttpOnly, Secure, SameSite)
 * - Automatisk regenerering av session-ID
 * - CSRF-skydd med tokens
 * - Flash-meddelanden
 * - Inloggningsskydd
 * - Databaslagrade sessioner för bättre säkerhet
 *
 * Databasstruktur (sessions-tabell):
 * - id: INT AUTO_INCREMENT
 * - user_id: INT (FK till users)
 * - token: VARCHAR(64) UNIQUE
 * - ip_address: VARCHAR(45)
 * - expires_at: DATETIME
 */

class Session
{
    private static bool $started = false;
    private static int $regenerateInterval = 1800; // 30 minuter
    private static int $sessionLifetime = 86400;   // 24 timmar

    /**
     * Starta sessionen säkert
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        // Kontrollera om headers redan skickats
        if (headers_sent()) {
            throw new Exception('Headers redan skickade, kan inte starta session');
        }

        // Konfigurera säkra session-cookies
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_set_cookie_params([
            'lifetime' => 0,                    // Stäng vid webbläsarens stängning
            'path' => '/',
            'domain' => '',
            'secure' => $secure,                // Endast HTTPS i produktion
            'httponly' => true,                 // Ej åtkomlig via JavaScript
            'samesite' => 'Strict'              // Skydd mot CSRF
        ]);

        // Säkrare session-namn
        session_name('PHPSESSID_SECURE');

        // Starta sessionen
        session_start();
        self::$started = true;

        // Regenerera session-ID regelbundet
        self::regenerateIfNeeded();

        // Validera session
        self::validateSession();

        // Validera databasession om inloggad
        self::validateDatabaseSession();
    }

    /**
     * Regenerera session-ID om det behövs
     */
    private static function regenerateIfNeeded(): void
    {
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            $_SESSION['_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        // Regenerera efter intervallet
        if (time() - $_SESSION['_created'] > self::$regenerateInterval) {
            self::regenerate();
        }
    }

    /**
     * Regenerera session-ID (t.ex. vid inloggning)
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }

    /**
     * Validera att sessionen inte kapats
     */
    private static function validateSession(): void
    {
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Kontrollera User-Agent
        if (isset($_SESSION['_user_agent']) && $_SESSION['_user_agent'] !== $currentUserAgent) {
            self::destroy();
            throw new Exception('Session ogiltig: User-Agent ändrad');
        }
    }

    /**
     * Validera sessionen mot databasen
     */
    private static function validateDatabaseSession(): void
    {
        // Hoppa över om inte inloggad
        if (!isset($_SESSION['_db_token'])) {
            return;
        }

        try {
            $db = Database::getInstance();
            $session = $db->fetchOne(
                "SELECT * FROM sessions WHERE token = ? AND expires_at > NOW()",
                [$_SESSION['_db_token']]
            );

            // Om sessionen inte finns eller har gått ut, logga ut
            if (!$session) {
                self::logout();
                return;
            }

            // Uppdatera utgångstid vid aktivitet
            $db->execute(
                "UPDATE sessions SET expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE token = ?",
                [self::$sessionLifetime, $_SESSION['_db_token']]
            );

        } catch (Exception $e) {
            // Vid databasfel, fortsätt med PHP-session
            error_log('Kunde inte validera databassession: ' . $e->getMessage());
        }
    }

    /**
     * Förstör sessionen helt
     */
    public static function destroy(): void
    {
        // Ta bort databassession om den finns
        if (isset($_SESSION['_db_token'])) {
            try {
                $db = Database::getInstance();
                $db->delete('sessions', 'token = ?', [$_SESSION['_db_token']]);
            } catch (Exception $e) {
                error_log('Kunde inte ta bort databassession: ' . $e->getMessage());
            }
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$started = false;
    }

    /**
     * Sätt ett värde i sessionen
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Hämta ett värde från sessionen
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Kontrollera om ett värde finns
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Ta bort ett värde från sessionen
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Generera CSRF-token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Hämta CSRF-token HTML-fält
     */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">';
    }

    /**
     * Verifiera CSRF-token
     */
    public static function verifyCsrfToken(?string $token): bool
    {
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    /**
     * Alias för verifyCsrfToken (för kompatibilitet)
     */
    public static function validateCsrf(?string $token): bool
    {
        return self::verifyCsrfToken($token);
    }

    /**
     * Verifiera CSRF-token från POST-request
     * Kastar exception om ogiltig
     */
    public static function requireCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!self::verifyCsrfToken($token)) {
            http_response_code(403);
            throw new Exception('Ogiltig CSRF-token');
        }
    }

    /**
     * Sätt flash-meddelande (visas en gång)
     */
    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    /**
     * Hämta och ta bort flash-meddelande
     */
    public static function getFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;

        if ($message !== null) {
            unset($_SESSION['_flash'][$key]);
        }

        return $message;
    }

    /**
     * Hämta alla flash-meddelanden
     */
    public static function getAllFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /**
     * Logga in användare och skapa databassession
     *
     * @param int $userId Användarens ID
     * @param array $userData Extra användardata att spara i sessionen
     * @return string Sessionstoken
     */
    public static function login(int $userId, array $userData = []): string
    {
        self::regenerate(); // Viktigt: regenerera session-ID vid inloggning

        // Generera unik sessionstoken
        $token = bin2hex(random_bytes(32));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $expiresAt = date('Y-m-d H:i:s', time() + self::$sessionLifetime);

        try {
            $db = Database::getInstance();

            // Ta bort gamla sessioner för denna användare (valfritt - behåll för multi-device)
            // $db->delete('sessions', 'user_id = ?', [$userId]);

            // Skapa ny session i databasen
            $db->insert('sessions', [
                'user_id' => $userId,
                'token' => $token,
                'ip_address' => $ipAddress,
                'expires_at' => $expiresAt
            ]);

        } catch (Exception $e) {
            error_log('Kunde inte skapa databassession: ' . $e->getMessage());
        }

        // Spara i PHP-session
        $_SESSION['_user_id'] = $userId;
        $_SESSION['_user_data'] = $userData;
        $_SESSION['_db_token'] = $token;
        $_SESSION['_logged_in_at'] = time();

        return $token;
    }

    /**
     * Logga ut användare
     */
    public static function logout(): void
    {
        // Ta bort databassession
        if (isset($_SESSION['_db_token'])) {
            try {
                $db = Database::getInstance();
                $db->delete('sessions', 'token = ?', [$_SESSION['_db_token']]);
            } catch (Exception $e) {
                error_log('Kunde inte ta bort databassession: ' . $e->getMessage());
            }
        }

        unset($_SESSION['_user_id']);
        unset($_SESSION['_user_data']);
        unset($_SESSION['_db_token']);
        unset($_SESSION['_logged_in_at']);

        self::regenerate();
    }

    /**
     * Logga ut användare från alla enheter
     *
     * @param int $userId Användarens ID
     */
    public static function logoutAllDevices(int $userId): void
    {
        try {
            $db = Database::getInstance();
            $db->delete('sessions', 'user_id = ?', [$userId]);
        } catch (Exception $e) {
            error_log('Kunde inte ta bort alla sessioner: ' . $e->getMessage());
        }

        // Logga även ut från nuvarande session
        self::logout();
    }

    /**
     * Kontrollera om användare är inloggad
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['_user_id']) && isset($_SESSION['_db_token']);
    }

    /**
     * Hämta inloggad användares ID
     */
    public static function getUserId(): ?int
    {
        return $_SESSION['_user_id'] ?? null;
    }

    /**
     * Hämta inloggad användares data
     */
    public static function getUserData(): array
    {
        return $_SESSION['_user_data'] ?? [];
    }

    /**
     * Uppdatera användardata i sessionen
     */
    public static function setUserData(array $data): void
    {
        $_SESSION['_user_data'] = $data;
    }

    /**
     * Kräv inloggning - redirect om ej inloggad
     */
    public static function requireLogin(string $redirectUrl = '/login.php'): void
    {
        if (!self::isLoggedIn()) {
            self::flash('error', 'Du måste logga in för att se denna sida');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Kräv specifik roll
     *
     * @param string $role Roll som krävs
     * @param string $redirectUrl URL att redirect till om ej behörig
     */
    public static function requireRole(string $role, string $redirectUrl = '/login.php'): void
    {
        self::requireLogin($redirectUrl);

        $userData = self::getUserData();
        if (!isset($userData['role']) || $userData['role'] !== $role) {
            self::flash('error', 'Du har inte behörighet att se denna sida');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Kräv admin-roll
     */
    public static function requireAdmin(string $redirectUrl = '/login.php'): void
    {
        self::requireRole('admin', $redirectUrl);
    }

    /**
     * Hämta alla aktiva sessioner för en användare
     *
     * @param int $userId Användarens ID
     * @return array Lista med sessioner
     */
    public static function getUserSessions(int $userId): array
    {
        try {
            $db = Database::getInstance();
            return $db->fetchAll(
                "SELECT id, ip_address, expires_at FROM sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY expires_at DESC",
                [$userId]
            );
        } catch (Exception $e) {
            error_log('Kunde inte hämta användarsessioner: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Rensa utgångna sessioner (kör via cron)
     *
     * @return int Antal borttagna sessioner
     */
    public static function cleanExpiredSessions(): int
    {
        try {
            $db = Database::getInstance();
            return $db->execute("DELETE FROM sessions WHERE expires_at < NOW()");
        } catch (Exception $e) {
            error_log('Kunde inte rensa utgångna sessioner: ' . $e->getMessage());
            return 0;
        }
    }
}
