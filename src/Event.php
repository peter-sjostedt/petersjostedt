<?php
/**
 * Event - Händelsehantering för RFID-systemet
 *
 * Skapar och hämtar händelser med tillhörande RFID-taggar.
 * Stödjer både nya event_type_id (FK) och gamla event_type (string) för bakåtkompatibilitet.
 */

class Event
{
    private Database $db;
    private static array $eventTypes = [];
    private ?EventType $eventTypeModel = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Hämta EventType-modellen (lazy load)
     */
    private function getEventTypeModel(): EventType
    {
        if ($this->eventTypeModel === null) {
            $this->eventTypeModel = new EventType();
        }
        return $this->eventTypeModel;
    }

    /**
     * Hämta alla definierade händelsetyper (bakåtkompatibilitet - använd EventType-klassen istället)
     * @deprecated Använd EventType::getAll() istället
     */
    public static function getEventTypes(): array
    {
        if (empty(self::$eventTypes)) {
            $seedFile = __DIR__ . '/../database/seeds/event_types.php';
            if (file_exists($seedFile)) {
                self::$eventTypes = require $seedFile;
            }
        }
        return self::$eventTypes;
    }

    /**
     * Kontrollera om en händelsetyp är giltig (bakåtkompatibilitet)
     * @deprecated Använd EventType::isValidCode() istället
     */
    public static function isValidEventType(string $eventType): bool
    {
        return array_key_exists($eventType, self::getEventTypes());
    }

    /**
     * Hämta etikett för en händelsetyp (bakåtkompatibilitet)
     * @deprecated Använd EventType::getNameByCode() istället
     */
    public static function getEventLabel(string $eventType, string $lang = 'sv'): string
    {
        $types = self::getEventTypes();
        if (isset($types[$eventType])) {
            $key = "label_{$lang}";
            return $types[$eventType][$key] ?? $types[$eventType]['label_sv'] ?? $eventType;
        }
        return $eventType;
    }

    /**
     * Skapa en ny händelse (bakåtkompatibel version med event_type string)
     *
     * @param string $eventType Händelsetyp (t.ex. 'rfid_sku_assigned')
     * @param string $organizationId Organisation som händelsen tillhör
     * @param array $metadata Händelsedata som JSON
     * @param array $rfids Lista med RFID EPC-koder att koppla
     * @param string|null $eventAt Tidpunkt för händelsen (default: nu)
     * @return int|false Event ID eller false vid fel
     * @deprecated Använd createWithType() för nya händelser
     */
    public function create(
        string $eventType,
        string $organizationId,
        array $metadata = [],
        array $rfids = [],
        ?string $eventAt = null
    ): int|false {
        $pdo = $this->db->getConnection();

        try {
            $pdo->beginTransaction();

            // Skapa händelsen
            $sql = "INSERT INTO events (organization_id, event_type, metadata, event_at)
                    VALUES (:org_id, :event_type, :metadata, :event_at)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'org_id' => $organizationId,
                'event_type' => $eventType,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                'event_at' => $eventAt ?? date('Y-m-d H:i:s')
            ]);

            $eventId = (int) $pdo->lastInsertId();

            // Koppla RFID-taggar om några angivits
            if (!empty($rfids)) {
                $insertSql = "INSERT INTO rfids_events (event_id, rfid) VALUES (:event_id, :rfid)";
                $insertStmt = $pdo->prepare($insertSql);

                foreach ($rfids as $rfid) {
                    $insertStmt->execute([
                        'event_id' => $eventId,
                        'rfid' => $rfid
                    ]);
                }
            }

            $pdo->commit();
            return $eventId;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Event::create failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Skapa händelse med event_type_id (ny version)
     *
     * @param int $eventTypeId FK till event_types
     * @param string $organizationId Organisation som händelsen tillhör
     * @param array $options Valfria parametrar:
     *   - template_id: FK till event_templates
     *   - from_unit_id: Avsändande enhet
     *   - to_unit_id: Mottagande enhet
     *   - scanned_by_unit_id: Enhet som skannade
     *   - metadata: Händelsedata som array
     *   - event_at: Tidpunkt för händelsen
     * @param array $rfids Lista med RFID EPC-koder att koppla
     * @return int|false Event ID eller false vid fel
     */
    public function createWithType(
        int $eventTypeId,
        string $organizationId,
        array $options = [],
        array $rfids = []
    ): int|false {
        $pdo = $this->db->getConnection();

        // Hämta event_type code för bakåtkompatibilitet
        $eventTypeModel = $this->getEventTypeModel();
        $eventType = $eventTypeModel->findById($eventTypeId);
        if (!$eventType) {
            error_log("Event::createWithType failed: Invalid event_type_id: $eventTypeId");
            return false;
        }

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO events
                    (organization_id, event_type, event_type_id, template_id, from_unit_id, to_unit_id, scanned_by_unit_id, metadata, event_at)
                    VALUES
                    (:org_id, :event_type, :event_type_id, :template_id, :from_unit_id, :to_unit_id, :scanned_by_unit_id, :metadata, :event_at)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'org_id' => $organizationId,
                'event_type' => $eventType['code'],
                'event_type_id' => $eventTypeId,
                'template_id' => $options['template_id'] ?? null,
                'from_unit_id' => $options['from_unit_id'] ?? null,
                'to_unit_id' => $options['to_unit_id'] ?? null,
                'scanned_by_unit_id' => $options['scanned_by_unit_id'] ?? null,
                'metadata' => json_encode($options['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
                'event_at' => $options['event_at'] ?? date('Y-m-d H:i:s')
            ]);

            $eventId = (int) $pdo->lastInsertId();

            // Koppla RFID-taggar
            if (!empty($rfids)) {
                $insertSql = "INSERT INTO rfids_events (event_id, rfid) VALUES (:event_id, :rfid)";
                $insertStmt = $pdo->prepare($insertSql);

                foreach ($rfids as $rfid) {
                    $insertStmt->execute([
                        'event_id' => $eventId,
                        'rfid' => $rfid
                    ]);
                }

                // Uppdatera RFID-statistik om det är en tvätt-händelse
                if ($eventType['increments_wash_count']) {
                    $this->incrementWashCount($rfids, $eventId);
                }

                // Uppdatera first/last event på RFID-taggar
                $this->updateRfidEventTimestamps($rfids, $eventId);
            }

            $pdo->commit();
            return $eventId;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Event::createWithType failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Skapa händelse från mall
     */
    public function createFromTemplate(
        int $templateId,
        string $organizationId,
        int $scannedByUnitId,
        array $rfids = [],
        array $additionalMetadata = []
    ): int|false {
        $templateModel = new EventTemplate();
        $template = $templateModel->findByIdAndOrganization($templateId, $organizationId);

        if (!$template) {
            error_log("Event::createFromTemplate failed: Template not found: $templateId");
            return false;
        }

        $options = [
            'template_id' => $templateId,
            'from_unit_id' => $template['unit_id'],
            'to_unit_id' => $template['target_unit_id'],
            'scanned_by_unit_id' => $scannedByUnitId,
            'metadata' => array_merge([
                'template_label' => $template['label'],
                'template_notes' => $template['notes']
            ], $additionalMetadata)
        ];

        $eventId = $this->createWithType(
            (int)$template['event_type_id'],
            $organizationId,
            $options,
            $rfids
        );

        // Om mallen är engångs, ta bort den efter användning
        if ($eventId && !$template['is_reusable']) {
            $templateModel->delete($templateId);
        }

        return $eventId;
    }

    /**
     * Öka tvätträknare för RFID-taggar
     */
    private function incrementWashCount(array $rfids, int $eventId): void
    {
        if (empty($rfids)) {
            return;
        }

        $pdo = $this->db->getConnection();
        $placeholders = implode(',', array_fill(0, count($rfids), '?'));

        $sql = "UPDATE rfids SET wash_count = wash_count + 1 WHERE epc IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($rfids);
    }

    /**
     * Uppdatera first/last event timestamps på RFID-taggar
     */
    private function updateRfidEventTimestamps(array $rfids, int $eventId): void
    {
        if (empty($rfids)) {
            return;
        }

        $pdo = $this->db->getConnection();
        $now = date('Y-m-d H:i:s');
        $placeholders = implode(',', array_fill(0, count($rfids), '?'));

        // Uppdatera last_event för alla
        $sql = "UPDATE rfids SET last_event_id = ?, last_event_at = ? WHERE epc IN ($placeholders)";
        $params = array_merge([$eventId, $now], $rfids);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Uppdatera first_event endast för de som inte har något
        $sql = "UPDATE rfids SET first_event_id = ?, first_event_at = ?
                WHERE epc IN ($placeholders) AND first_event_id IS NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Hämta händelse med ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT e.*,
                       GROUP_CONCAT(re.rfid) as rfids
                FROM events e
                LEFT JOIN rfids_events re ON e.id = re.event_id
                WHERE e.id = :id
                GROUP BY e.id";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            $event['metadata'] = json_decode($event['metadata'], true) ?? [];
            $event['rfids'] = $event['rfids'] ? explode(',', $event['rfids']) : [];
            $event['event_label'] = self::getEventLabel($event['event_type']);
        }

        return $event ?: null;
    }

    /**
     * Hämta händelser för en organisation
     */
    public function findByOrganization(
        string $organizationId,
        ?string $eventType = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $params = ['org_id' => $organizationId];

        $sql = "SELECT e.*,
                       GROUP_CONCAT(re.rfid) as rfids
                FROM events e
                LEFT JOIN rfids_events re ON e.id = re.event_id
                WHERE e.organization_id = :org_id";

        if ($eventType) {
            $sql .= " AND e.event_type = :event_type";
            $params['event_type'] = $eventType;
        }

        $sql .= " GROUP BY e.id ORDER BY e.event_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':org_id', $organizationId);
        if ($eventType) {
            $stmt->bindValue(':event_type', $eventType);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as &$event) {
            $event['metadata'] = json_decode($event['metadata'], true) ?? [];
            $event['rfids'] = $event['rfids'] ? explode(',', $event['rfids']) : [];
            $event['event_label'] = self::getEventLabel($event['event_type']);
        }

        return $events;
    }

    /**
     * Hämta händelser för en RFID-tagg
     */
    public function findByRfid(string $rfid, int $limit = 100): array
    {
        $sql = "SELECT e.*,
                       GROUP_CONCAT(re2.rfid) as rfids
                FROM events e
                INNER JOIN rfids_events re ON e.id = re.event_id AND re.rfid = :rfid
                LEFT JOIN rfids_events re2 ON e.id = re2.event_id
                GROUP BY e.id
                ORDER BY e.event_at DESC
                LIMIT :limit";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':rfid', $rfid);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as &$event) {
            $event['metadata'] = json_decode($event['metadata'], true) ?? [];
            $event['rfids'] = $event['rfids'] ? explode(',', $event['rfids']) : [];
            $event['event_label'] = self::getEventLabel($event['event_type']);
        }

        return $events;
    }

    /**
     * Räkna händelser per typ för en organisation
     */
    public function countByType(string $organizationId): array
    {
        $sql = "SELECT event_type, COUNT(*) as count
                FROM events
                WHERE organization_id = :org_id
                GROUP BY event_type
                ORDER BY count DESC";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute(['org_id' => $organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper: Skapa rfid_sku_assigned händelse
     */
    public function rfidSkuAssigned(
        string $rfid,
        string $organizationId,
        string $sku,
        int $articleId,
        ?int $userId = null,
        ?int $unitId = null
    ): int|false {
        return $this->create(
            'rfid_sku_assigned',
            $organizationId,
            [
                'rfid' => $rfid,
                'organization_id' => $organizationId,
                'sku' => $sku,
                'article_id' => $articleId,
                'created_by' => [
                    'user_id' => $userId,
                    'unit_id' => $unitId
                ]
            ],
            [$rfid]
        );
    }

    /**
     * Helper: Skapa shipment_sent händelse
     */
    public function shipmentSent(
        string $shipmentId,
        string $fromOrgId,
        string $toOrgId,
        array $rfids,
        ?int $userId = null,
        ?int $unitId = null
    ): int|false {
        return $this->create(
            'shipment_sent',
            $fromOrgId,
            [
                'shipment_id' => $shipmentId,
                'from_organization_id' => $fromOrgId,
                'to_organization_id' => $toOrgId,
                'rfid_count' => count($rfids),
                'created_by' => [
                    'user_id' => $userId,
                    'unit_id' => $unitId
                ]
            ],
            $rfids
        );
    }

    /**
     * Helper: Skapa shipment_received händelse
     */
    public function shipmentReceived(
        string $shipmentId,
        string $fromOrgId,
        string $toOrgId,
        array $rfids,
        ?int $expectedCount = null,
        ?int $userId = null,
        ?int $unitId = null
    ): int|false {
        return $this->create(
            'shipment_received',
            $toOrgId,
            [
                'shipment_id' => $shipmentId,
                'from_organization_id' => $fromOrgId,
                'to_organization_id' => $toOrgId,
                'rfid_count' => count($rfids),
                'expected_count' => $expectedCount,
                'created_by' => [
                    'user_id' => $userId,
                    'unit_id' => $unitId
                ]
            ],
            $rfids
        );
    }
}
