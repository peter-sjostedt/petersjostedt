<?php
/**
 * Shipment - Hantering av försändelser mellan organisationer
 *
 * En försändelse representerar en leverans av varor från en organisation till en annan.
 * QR-koder genereras för att möjliggöra skanning vid avsändning och mottagning.
 *
 * Status:
 * - prepared: Försändelsen är förberedd men inte skickad
 * - shipped: Försändelsen har skickats (RFID skannade vid avsändning)
 * - received: Försändelsen har mottagits (RFID skannade vid mottagning)
 * - cancelled: Försändelsen har avbrutits
 */

class Shipment
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generera nästa QR-kod för försändelse
     */
    public function generateQrCode(): string
    {
        $pdo = $this->db->getPdo();

        // Hämta nästa nummer för året
        $stmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as next_num
            FROM shipments
            WHERE YEAR(created_at) = YEAR(NOW())
        ");
        $stmt->execute();
        $nextNum = (int) $stmt->fetchColumn();

        return 'SH-' . date('Y') . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Skapa en ny försändelse
     *
     * @param string $fromOrgId Avsändande organisation
     * @param string $toOrgId Mottagande organisation
     * @param array $options Valfria parametrar
     * @return int|false Shipment-ID eller false vid fel
     */
    public function create(
        string $fromOrgId,
        string $toOrgId,
        array $options = []
    ): int|false {
        $pdo = $this->db->getPdo();

        $qrCode = $options['qr_code'] ?? $this->generateQrCode();

        try {
            $sql = "INSERT INTO shipments
                    (qr_code, from_org_id, to_org_id, from_unit_id, to_unit_id,
                     sales_order_id, purchase_order_id, notes, metadata, created_by_user_id)
                    VALUES (:qr_code, :from_org_id, :to_org_id, :from_unit_id, :to_unit_id,
                            :sales_order_id, :purchase_order_id, :notes, :metadata, :created_by)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'qr_code' => $qrCode,
                'from_org_id' => $fromOrgId,
                'to_org_id' => $toOrgId,
                'from_unit_id' => $options['from_unit_id'] ?? null,
                'to_unit_id' => $options['to_unit_id'] ?? null,
                'sales_order_id' => $options['sales_order_id'] ?? null,
                'purchase_order_id' => $options['purchase_order_id'] ?? null,
                'notes' => $options['notes'] ?? null,
                'metadata' => isset($options['metadata']) ? json_encode($options['metadata']) : null,
                'created_by' => $options['created_by_user_id'] ?? null
            ]);

            return (int) $pdo->lastInsertId();

        } catch (PDOException $e) {
            error_log("Shipment::create failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Uppdatera en försändelse
     */
    public function update(int $id, array $data): bool
    {
        $pdo = $this->db->getPdo();

        $allowedFields = [
            'from_unit_id', 'to_unit_id', 'sales_order_id', 'purchase_order_id',
            'status', 'notes', 'metadata', 'shipped_at', 'shipped_by_user_id',
            'received_at', 'received_by_user_id'
        ];

        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $value = $data[$field];
                if ($field === 'metadata' && is_array($value)) {
                    $value = json_encode($value);
                }
                $params[$field] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        try {
            $sql = "UPDATE shipments SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Shipment::update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Markera försändelse som skickad
     */
    public function markAsShipped(int $id, ?int $userId = null): bool
    {
        return $this->update($id, [
            'status' => 'shipped',
            'shipped_at' => date('Y-m-d H:i:s'),
            'shipped_by_user_id' => $userId
        ]);
    }

    /**
     * Markera försändelse som mottagen
     */
    public function markAsReceived(int $id, ?int $userId = null): bool
    {
        return $this->update($id, [
            'status' => 'received',
            'received_at' => date('Y-m-d H:i:s'),
            'received_by_user_id' => $userId
        ]);
    }

    /**
     * Avbryt försändelse
     */
    public function cancel(int $id): bool
    {
        return $this->update($id, ['status' => 'cancelled']);
    }

    /**
     * Ta bort en försändelse (endast om status är 'prepared')
     */
    public function delete(int $id): bool
    {
        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare("DELETE FROM shipments WHERE id = :id AND status = 'prepared'");
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("Shipment::delete failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hämta försändelse med ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT s.*,
                       fo.name as from_org_name,
                       tos.name as to_org_name,
                       fu.name as from_unit_name,
                       tu.name as to_unit_name,
                       cu.name as created_by_name,
                       su.name as shipped_by_name,
                       ru.name as received_by_name
                FROM shipments s
                LEFT JOIN organizations fo ON s.from_org_id = fo.id
                LEFT JOIN organizations tos ON s.to_org_id = tos.id
                LEFT JOIN units fu ON s.from_unit_id = fu.id
                LEFT JOIN units tu ON s.to_unit_id = tu.id
                LEFT JOIN users cu ON s.created_by_user_id = cu.id
                LEFT JOIN users su ON s.shipped_by_user_id = su.id
                LEFT JOIN users ru ON s.received_by_user_id = ru.id
                WHERE s.id = :id";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shipment ?: null;
    }

    /**
     * Hämta försändelse med QR-kod
     */
    public function findByQrCode(string $qrCode): ?array
    {
        $sql = "SELECT s.*,
                       fo.name as from_org_name,
                       tos.name as to_org_name
                FROM shipments s
                LEFT JOIN organizations fo ON s.from_org_id = fo.id
                LEFT JOIN organizations tos ON s.to_org_id = tos.id
                WHERE s.qr_code = :qr_code";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['qr_code' => $qrCode]);
        $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shipment ?: null;
    }

    /**
     * Hämta utgående försändelser för en organisation
     */
    public function findOutgoing(
        string $organizationId,
        ?string $status = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $params = ['org_id' => $organizationId];

        $sql = "SELECT s.*,
                       tos.name as to_org_name,
                       fu.name as from_unit_name,
                       tu.name as to_unit_name
                FROM shipments s
                LEFT JOIN organizations tos ON s.to_org_id = tos.id
                LEFT JOIN units fu ON s.from_unit_id = fu.id
                LEFT JOIN units tu ON s.to_unit_id = tu.id
                WHERE s.from_org_id = :org_id";

        if ($status !== null) {
            $sql .= " AND s.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':org_id', $organizationId);
        if ($status !== null) {
            $stmt->bindValue(':status', $status);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hämta inkommande försändelser för en organisation
     */
    public function findIncoming(
        string $organizationId,
        ?string $status = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $params = ['org_id' => $organizationId];

        $sql = "SELECT s.*,
                       fo.name as from_org_name,
                       fu.name as from_unit_name,
                       tu.name as to_unit_name
                FROM shipments s
                LEFT JOIN organizations fo ON s.from_org_id = fo.id
                LEFT JOIN units fu ON s.from_unit_id = fu.id
                LEFT JOIN units tu ON s.to_unit_id = tu.id
                WHERE s.to_org_id = :org_id";

        if ($status !== null) {
            $sql .= " AND s.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':org_id', $organizationId);
        if ($status !== null) {
            $stmt->bindValue(':status', $status);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hämta försändelse med ID och verifiera ägarskap (antingen from eller to)
     */
    public function findByIdAndOrganization(int $id, string $organizationId): ?array
    {
        $sql = "SELECT s.*,
                       fo.name as from_org_name,
                       tos.name as to_org_name,
                       fu.name as from_unit_name,
                       tu.name as to_unit_name
                FROM shipments s
                LEFT JOIN organizations fo ON s.from_org_id = fo.id
                LEFT JOIN organizations tos ON s.to_org_id = tos.id
                LEFT JOIN units fu ON s.from_unit_id = fu.id
                LEFT JOIN units tu ON s.to_unit_id = tu.id
                WHERE s.id = :id
                AND (s.from_org_id = :org_id OR s.to_org_id = :org_id2)";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'org_id' => $organizationId,
            'org_id2' => $organizationId
        ]);
        $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shipment ?: null;
    }

    /**
     * Räkna utgående försändelser per organisation
     */
    public function countOutgoing(string $organizationId, ?string $status = null): int
    {
        $params = ['org_id' => $organizationId];

        $sql = "SELECT COUNT(*) FROM shipments WHERE from_org_id = :org_id";

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Räkna inkommande försändelser per organisation
     */
    public function countIncoming(string $organizationId, ?string $status = null): int
    {
        $params = ['org_id' => $organizationId];

        $sql = "SELECT COUNT(*) FROM shipments WHERE to_org_id = :org_id";

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Hitta försändelse baserat på order-ID:n (för dublettdetektering)
     *
     * Matchar på kombinationen av from_org_id + to_org_id + sales_order_id + purchase_order_id.
     * Om båda order-ID:n är null returneras null (kan inte matcha utan minst ett order-ID).
     */
    public function findByOrderIds(
        string $fromOrgId,
        string $toOrgId,
        ?string $salesOrderId,
        ?string $purchaseOrderId
    ): ?array {
        // Kräver minst ett order-ID för att kunna matcha
        if ($salesOrderId === null && $purchaseOrderId === null) {
            return null;
        }

        $pdo = $this->db->getPdo();

        $sql = "SELECT * FROM shipments
                WHERE from_org_id = :from_org_id
                AND to_org_id = :to_org_id";

        $params = [
            'from_org_id' => $fromOrgId,
            'to_org_id' => $toOrgId
        ];

        // Matcha på sales_order_id (hanterar NULL-jämförelse)
        if ($salesOrderId !== null) {
            $sql .= " AND sales_order_id = :sales_order_id";
            $params['sales_order_id'] = $salesOrderId;
        } else {
            $sql .= " AND sales_order_id IS NULL";
        }

        // Matcha på purchase_order_id (hanterar NULL-jämförelse)
        if ($purchaseOrderId !== null) {
            $sql .= " AND purchase_order_id = :purchase_order_id";
            $params['purchase_order_id'] = $purchaseOrderId;
        } else {
            $sql .= " AND purchase_order_id IS NULL";
        }

        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shipment ?: null;
    }

    /**
     * Hämta statusbadge-klass
     */
    public static function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'prepared' => 'badge-new',
            'shipped' => 'badge-used',
            'received' => 'badge-success',
            'cancelled' => 'badge-inactive',
            default => ''
        };
    }
}
