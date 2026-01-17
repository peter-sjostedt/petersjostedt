<?php
/**
 * EventType - Hantering av händelsetyper
 *
 * Hämtar fördefinierade händelsetyper från databasen.
 * Cachar resultat för prestanda.
 */

class EventType
{
    private Database $db;
    private static ?array $cachedTypes = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Hämta alla aktiva händelsetyper
     */
    public function getAll(): array
    {
        if (self::$cachedTypes !== null) {
            return self::$cachedTypes;
        }

        $sql = "SELECT * FROM event_types WHERE is_active = 1 ORDER BY sort_order, name_sv";
        $stmt = $this->db->getPdo()->query($sql);
        self::$cachedTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return self::$cachedTypes;
    }

    /**
     * Hämta händelsetyp med ID
     */
    public function findById(int $id): ?array
    {
        foreach ($this->getAll() as $type) {
            if ((int)$type['id'] === $id) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Hämta händelsetyp med kod
     */
    public function findByCode(string $code): ?array
    {
        foreach ($this->getAll() as $type) {
            if ($type['code'] === $code) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Hämta namn för händelsetyp
     */
    public function getName(int $id, string $lang = 'sv'): string
    {
        $type = $this->findById($id);
        if (!$type) {
            return '';
        }

        $key = "name_{$lang}";
        return $type[$key] ?? $type['name_sv'] ?? '';
    }

    /**
     * Hämta namn via kod
     */
    public function getNameByCode(string $code, string $lang = 'sv'): string
    {
        $type = $this->findByCode($code);
        if (!$type) {
            return $code;
        }

        $key = "name_{$lang}";
        return $type[$key] ?? $type['name_sv'] ?? $code;
    }

    /**
     * Kontrollera om händelsetyp är giltig
     */
    public function isValid(int $id): bool
    {
        return $this->findById($id) !== null;
    }

    /**
     * Kontrollera om händelsetyp-kod är giltig
     */
    public function isValidCode(string $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    /**
     * Kontrollera om händelsetyp är en transfer
     */
    public function isTransfer(int $id): bool
    {
        $type = $this->findById($id);
        return $type && (bool)$type['is_transfer'];
    }

    /**
     * Kontrollera om händelsetyp ökar tvätträknaren
     */
    public function incrementsWashCount(int $id): bool
    {
        $type = $this->findById($id);
        return $type && (bool)$type['increments_wash_count'];
    }

    /**
     * Hämta transfer-typer
     */
    public function getTransferTypes(): array
    {
        return array_filter($this->getAll(), fn($t) => (bool)$t['is_transfer']);
    }

    /**
     * Hämta typer för dropdown (id => name)
     */
    public function getDropdownOptions(string $lang = 'sv'): array
    {
        $options = [];
        $key = "name_{$lang}";

        foreach ($this->getAll() as $type) {
            $options[$type['id']] = $type[$key] ?? $type['name_sv'];
        }

        return $options;
    }

    /**
     * Rensa cache (för test/reload)
     */
    public static function clearCache(): void
    {
        self::$cachedTypes = null;
    }
}
