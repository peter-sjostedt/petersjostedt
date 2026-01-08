<?php
/**
 * Settings - Inställningshantering
 *
 * Hanterar key-value inställningar från databasen
 * med caching för bättre prestanda.
 *
 * Databasstruktur (settings-tabell):
 * - id: INT AUTO_INCREMENT
 * - setting_key: VARCHAR(100) UNIQUE
 * - setting_value: TEXT
 */

class Settings
{
    private static ?Settings $instance = null;
    private Database $db;
    private array $cache = [];
    private bool $cacheLoaded = false;

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
    public static function getInstance(): Settings
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Ladda alla inställningar till cache
     */
    private function loadCache(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        try {
            $results = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");

            foreach ($results as $row) {
                $this->cache[$row['setting_key']] = $row['setting_value'];
            }

            $this->cacheLoaded = true;

        } catch (Exception $e) {
            error_log('Kunde inte ladda inställningar: ' . $e->getMessage());
        }
    }

    /**
     * Hämta en inställning
     *
     * @param string $key Inställningsnyckeln
     * @param mixed $default Standardvärde om nyckeln inte finns
     * @return mixed Inställningsvärdet
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->loadCache();

        if (isset($this->cache[$key])) {
            return $this->unserializeValue($this->cache[$key]);
        }

        return $default;
    }

    /**
     * Sätt en inställning
     *
     * @param string $key Inställningsnyckeln
     * @param mixed $value Värdet att spara
     * @return bool True om det lyckades
     */
    public function set(string $key, mixed $value): bool
    {
        $serializedValue = $this->serializeValue($value);

        try {
            // Kontrollera om inställningen redan finns
            $existing = $this->db->fetchOne(
                "SELECT id FROM settings WHERE setting_key = ?",
                [$key]
            );

            if ($existing) {
                // Uppdatera befintlig
                $this->db->update(
                    'settings',
                    ['setting_value' => $serializedValue],
                    'setting_key = ?',
                    [$key]
                );
            } else {
                // Skapa ny
                $this->db->insert('settings', [
                    'setting_key' => $key,
                    'setting_value' => $serializedValue
                ]);
            }

            // Uppdatera cache
            $this->cache[$key] = $serializedValue;

            return true;

        } catch (Exception $e) {
            error_log('Kunde inte spara inställning: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ta bort en inställning
     *
     * @param string $key Inställningsnyckeln
     * @return bool True om det lyckades
     */
    public function delete(string $key): bool
    {
        try {
            $affected = $this->db->delete('settings', 'setting_key = ?', [$key]);

            // Ta bort från cache
            unset($this->cache[$key]);

            return $affected > 0;

        } catch (Exception $e) {
            error_log('Kunde inte ta bort inställning: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kontrollera om en inställning finns
     *
     * @param string $key Inställningsnyckeln
     * @return bool True om den finns
     */
    public function has(string $key): bool
    {
        $this->loadCache();
        return isset($this->cache[$key]);
    }

    /**
     * Hämta alla inställningar
     *
     * @return array Associativ array med alla inställningar
     */
    public function getAll(): array
    {
        $this->loadCache();

        $result = [];
        foreach ($this->cache as $key => $value) {
            $result[$key] = $this->unserializeValue($value);
        }

        return $result;
    }

    /**
     * Hämta flera inställningar på en gång
     *
     * @param array $keys Lista med nycklar
     * @param array $defaults Standardvärden (key => default)
     * @return array Associativ array med värden
     */
    public function getMany(array $keys, array $defaults = []): array
    {
        $result = [];

        foreach ($keys as $key) {
            $default = $defaults[$key] ?? null;
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * Sätt flera inställningar på en gång
     *
     * @param array $settings Associativ array med key => value
     * @return bool True om alla lyckades
     */
    public function setMany(array $settings): bool
    {
        $success = true;

        try {
            $this->db->beginTransaction();

            foreach ($settings as $key => $value) {
                if (!$this->set($key, $value)) {
                    $success = false;
                }
            }

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Kunde inte spara flera inställningar: ' . $e->getMessage());
            return false;
        }

        return $success;
    }

    /**
     * Serialisera ett värde för lagring
     * Hanterar arrays och objekt
     */
    private function serializeValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * Avserialisera ett värde från lagring
     * Försöker detektera typ automatiskt
     */
    private function unserializeValue(string $value): mixed
    {
        // Försök tolka som JSON (array/object)
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            return $decoded;
        }

        // Kolla om det är ett nummer
        if (is_numeric($value)) {
            // Heltal eller decimaltal
            if (strpos($value, '.') !== false) {
                return (float) $value;
            }
            return (int) $value;
        }

        // Kolla om det är boolean-liknande
        if ($value === '1' || strtolower($value) === 'true') {
            return true;
        }
        if ($value === '0' || strtolower($value) === 'false') {
            return false;
        }

        // Returnera som sträng
        return $value;
    }

    /**
     * Rensa cache (tvinga omladdning nästa gång)
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->cacheLoaded = false;
    }

    /**
     * Statisk genväg för att hämta inställning
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return self::getInstance()->get($key, $default);
    }

    /**
     * Statisk genväg för att sätta inställning
     */
    public static function setValue(string $key, mixed $value): bool
    {
        return self::getInstance()->set($key, $value);
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
