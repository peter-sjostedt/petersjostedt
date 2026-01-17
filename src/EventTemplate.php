<?php
/**
 * EventTemplate - Hantering av händelsemallar (förberedelser)
 *
 * Mallar skapas med QR-koder och används vid skanning för att skapa händelser.
 * is_reusable = TRUE: Repetitiva mallar (kan användas flera gånger)
 * is_reusable = FALSE: Engångsmallar (raderas efter användning)
 */

class EventTemplate
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Skapa en ny händelsemall
     *
     * @param string $organizationId Organisation som äger mallen
     * @param int $eventTypeId Typ av händelse
     * @param string $label Namn/etikett för mallen
     * @param array $options Valfria parametrar (unit_id, target_unit_id, is_reusable, notes, created_by_user_id)
     * @return int|false Mall-ID eller false vid fel
     */
    public function create(
        string $organizationId,
        int $eventTypeId,
        string $label,
        array $options = []
    ): int|false {
        $pdo = $this->db->getPdo();

        try {
            $sql = "INSERT INTO event_templates
                    (organization_id, event_type_id, label, unit_id, target_unit_id, is_reusable, notes, created_by_user_id)
                    VALUES (:org_id, :event_type_id, :label, :unit_id, :target_unit_id, :is_reusable, :notes, :created_by)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'org_id' => $organizationId,
                'event_type_id' => $eventTypeId,
                'label' => $label,
                'unit_id' => $options['unit_id'] ?? null,
                'target_unit_id' => $options['target_unit_id'] ?? null,
                'is_reusable' => isset($options['is_reusable']) ? (int)$options['is_reusable'] : 1,
                'notes' => $options['notes'] ?? null,
                'created_by' => $options['created_by_user_id'] ?? null
            ]);

            return (int) $pdo->lastInsertId();

        } catch (PDOException $e) {
            error_log("EventTemplate::create failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Uppdatera en händelsemall
     */
    public function update(int $id, array $data): bool
    {
        $pdo = $this->db->getPdo();

        $allowedFields = ['label', 'unit_id', 'target_unit_id', 'is_reusable', 'notes'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        try {
            $sql = "UPDATE event_templates SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("EventTemplate::update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ta bort en händelsemall
     */
    public function delete(int $id): bool
    {
        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare("DELETE FROM event_templates WHERE id = :id");
            return $stmt->execute(['id' => $id]);

        } catch (PDOException $e) {
            error_log("EventTemplate::delete failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hämta mall med ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT et.*,
                       evt.code as event_type_code,
                       evt.name_sv as event_type_name_sv,
                       evt.name_en as event_type_name_en,
                       u.name as unit_name,
                       tu.name as target_unit_name
                FROM event_templates et
                LEFT JOIN event_types evt ON et.event_type_id = evt.id
                LEFT JOIN units u ON et.unit_id = u.id
                LEFT JOIN units tu ON et.target_unit_id = tu.id
                WHERE et.id = :id";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        return $template ?: null;
    }

    /**
     * Hämta mall med ID och organisations-ID (för säkerhet)
     */
    public function findByIdAndOrganization(int $id, string $organizationId): ?array
    {
        $sql = "SELECT et.*,
                       evt.code as event_type_code,
                       evt.name_sv as event_type_name_sv,
                       evt.name_en as event_type_name_en,
                       u.name as unit_name,
                       tu.name as target_unit_name
                FROM event_templates et
                LEFT JOIN event_types evt ON et.event_type_id = evt.id
                LEFT JOIN units u ON et.unit_id = u.id
                LEFT JOIN units tu ON et.target_unit_id = tu.id
                WHERE et.id = :id AND et.organization_id = :org_id";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        return $template ?: null;
    }

    /**
     * Hämta mallar för en organisation
     */
    public function findByOrganization(
        string $organizationId,
        ?bool $isReusable = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $params = ['org_id' => $organizationId];

        $sql = "SELECT et.*,
                       evt.code as event_type_code,
                       evt.name_sv as event_type_name_sv,
                       evt.name_en as event_type_name_en,
                       u.name as unit_name,
                       tu.name as target_unit_name
                FROM event_templates et
                LEFT JOIN event_types evt ON et.event_type_id = evt.id
                LEFT JOIN units u ON et.unit_id = u.id
                LEFT JOIN units tu ON et.target_unit_id = tu.id
                WHERE et.organization_id = :org_id";

        if ($isReusable !== null) {
            $sql .= " AND et.is_reusable = :is_reusable";
            $params['is_reusable'] = (int)$isReusable;
        }

        $sql .= " ORDER BY et.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':org_id', $organizationId);
        if ($isReusable !== null) {
            $stmt->bindValue(':is_reusable', (int)$isReusable, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hämta mallar per enhet
     */
    public function findByUnit(int $unitId, ?bool $isReusable = null): array
    {
        $params = ['unit_id' => $unitId];

        $sql = "SELECT et.*,
                       evt.code as event_type_code,
                       evt.name_sv as event_type_name_sv,
                       evt.name_en as event_type_name_en,
                       u.name as unit_name,
                       tu.name as target_unit_name
                FROM event_templates et
                LEFT JOIN event_types evt ON et.event_type_id = evt.id
                LEFT JOIN units u ON et.unit_id = u.id
                LEFT JOIN units tu ON et.target_unit_id = tu.id
                WHERE et.unit_id = :unit_id";

        if ($isReusable !== null) {
            $sql .= " AND et.is_reusable = :is_reusable";
            $params['is_reusable'] = (int)$isReusable;
        }

        $sql .= " ORDER BY et.created_at DESC";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hämta mall via label (för import/sökning)
     */
    public function findByLabel(string $organizationId, string $label): ?array
    {
        $sql = "SELECT et.*,
                       evt.code as event_type_code,
                       evt.name_sv as event_type_name_sv,
                       evt.name_en as event_type_name_en
                FROM event_templates et
                LEFT JOIN event_types evt ON et.event_type_id = evt.id
                WHERE et.organization_id = :org_id
                AND LOWER(et.label) = LOWER(:label)";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['org_id' => $organizationId, 'label' => $label]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        return $template ?: null;
    }

    /**
     * Räkna mallar per organisation
     */
    public function countByOrganization(string $organizationId, ?bool $isReusable = null): int
    {
        $params = ['org_id' => $organizationId];

        $sql = "SELECT COUNT(*) FROM event_templates WHERE organization_id = :org_id";

        if ($isReusable !== null) {
            $sql .= " AND is_reusable = :is_reusable";
            $params['is_reusable'] = (int)$isReusable;
        }

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
