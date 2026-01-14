<?php
/**
 * Organization - Organisationshantering
 *
 * Hanterar:
 * - Skapa nya organisationer
 * - Hämta organisationer
 * - Uppdatera organisationsuppgifter
 * - Ta bort organisationer
 * - Hantera article_schema (JSON)
 */

class Organization
{
    private Database $db;

    // Organisationstyper
    public const TYPE_SYSTEM = 'system';
    public const TYPE_CUSTOMER = 'customer';

    /**
     * Konstruktor - hämta databasinstans
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Skapa en ny organisation
     *
     * @param string $id Organisationsnummer (t.ex. SE556123-4567)
     * @param string $name Organisationsnamn
     * @param string $orgType Typ (system eller customer)
     * @param array $data Övriga fält (address, city, email, etc.)
     * @return string|false Organisation-ID eller false vid fel
     */
    public function create(string $id, string $name, string $orgType = self::TYPE_CUSTOMER, array $data = []): string|false
    {
        // Validera ID
        if (empty($id) || strlen($id) > 20) {
            return false;
        }

        // Kontrollera att ID inte redan finns
        if ($this->findById($id)) {
            return false;
        }

        try {
            $insertData = [
                'id' => $id,
                'name' => $name,
                'org_type' => $orgType,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Tillåtna fält
            $allowed = ['article_schema', 'address', 'postal_code', 'city', 'country', 'phone', 'email', 'is_active'];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $insertData[$field] = $data[$field];
                }
            }

            // Konvertera article_schema till JSON om det är en array
            if (isset($insertData['article_schema']) && is_array($insertData['article_schema'])) {
                $insertData['article_schema'] = json_encode($insertData['article_schema']);
            }

            $this->db->insert('organizations', $insertData);

            return $id;

        } catch (Exception $e) {
            error_log('Fel vid skapande av organisation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hämta organisation via ID
     *
     * @param string $id Organisation-ID
     * @return array|null Organisationsdata eller null
     */
    public function findById(string $id): ?array
    {
        $org = $this->db->fetchOne(
            "SELECT * FROM organizations WHERE id = ?",
            [$id]
        );

        if ($org && !empty($org['article_schema'])) {
            $org['article_schema'] = json_decode($org['article_schema'], true);
        }

        return $org;
    }

    /**
     * Hämta alla organisationer
     *
     * @param string|null $orgType Filtrera på typ (null = alla)
     * @param bool $activeOnly Endast aktiva organisationer
     * @return array Lista med organisationer
     */
    public function findAll(?string $orgType = null, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM organizations WHERE 1=1";
        $params = [];

        if ($orgType !== null) {
            $sql .= " AND org_type = ?";
            $params[] = $orgType;
        }

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY name ASC";

        $orgs = $this->db->fetchAll($sql, $params);

        // Avkoda article_schema för alla organisationer
        foreach ($orgs as &$org) {
            if (!empty($org['article_schema'])) {
                $org['article_schema'] = json_decode($org['article_schema'], true);
            }
        }

        return $orgs;
    }

    /**
     * Hämta endast kund-organisationer
     *
     * @param bool $activeOnly Endast aktiva
     * @return array Lista med kund-organisationer
     */
    public function findCustomers(bool $activeOnly = true): array
    {
        return $this->findAll(self::TYPE_CUSTOMER, $activeOnly);
    }

    /**
     * Uppdatera organisation
     *
     * @param string $id Organisation-ID
     * @param array $data Data att uppdatera
     * @return bool True om uppdateringen lyckades
     */
    public function update(string $id, array $data): bool
    {
        // Tillåtna fält att uppdatera
        $allowed = ['name', 'org_type', 'article_schema', 'address', 'postal_code', 'city', 'country', 'phone', 'email', 'is_active'];
        $updateData = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        // Konvertera article_schema till JSON om det är en array
        if (isset($updateData['article_schema']) && is_array($updateData['article_schema'])) {
            $updateData['article_schema'] = json_encode($updateData['article_schema']);
        }

        // Lägg till updated_at
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $affected = $this->db->update('organizations', $updateData, 'id = ?', [$id]);
            return $affected > 0;

        } catch (Exception $e) {
            error_log('Fel vid uppdatering av organisation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ta bort organisation
     * OBS: Raderar även alla relaterade units, articles, rfids etc på grund av CASCADE
     *
     * @param string $id Organisation-ID
     * @return bool True om borttagningen lyckades
     */
    public function delete(string $id): bool
    {
        // Förhindra radering av system-organisation
        $org = $this->findById($id);
        if ($org && $org['org_type'] === self::TYPE_SYSTEM) {
            error_log('Försök att radera system-organisation');
            return false;
        }

        try {
            $affected = $this->db->delete('organizations', 'id = ?', [$id]);
            return $affected > 0;

        } catch (Exception $e) {
            error_log('Fel vid borttagning av organisation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Räkna totalt antal organisationer
     *
     * @param string|null $orgType Filtrera på typ
     * @param bool $activeOnly Endast aktiva
     * @return int Antal organisationer
     */
    public function count(?string $orgType = null, bool $activeOnly = true): int
    {
        $sql = "SELECT COUNT(*) FROM organizations WHERE 1=1";
        $params = [];

        if ($orgType !== null) {
            $sql .= " AND org_type = ?";
            $params[] = $orgType;
        }

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    /**
     * Sök organisationer
     *
     * @param string $query Sökterm
     * @param bool $activeOnly Endast aktiva
     * @return array Matchande organisationer
     */
    public function search(string $query, bool $activeOnly = true): array
    {
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT * FROM organizations
                WHERE (name LIKE ? OR id LIKE ? OR email LIKE ?)";
        $params = [$searchTerm, $searchTerm, $searchTerm];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY name";

        $orgs = $this->db->fetchAll($sql, $params);

        // Avkoda article_schema för alla organisationer
        foreach ($orgs as &$org) {
            if (!empty($org['article_schema'])) {
                $org['article_schema'] = json_decode($org['article_schema'], true);
            }
        }

        return $orgs;
    }

    /**
     * Hämta article schema för en organisation
     *
     * @param string $id Organisation-ID
     * @return array|null Schema eller null
     */
    public function getArticleSchema(string $id): ?array
    {
        $org = $this->findById($id);
        return $org['article_schema'] ?? null;
    }

    /**
     * Uppdatera article schema för en organisation
     *
     * @param string $id Organisation-ID
     * @param array $schema Schema-definition
     * @return bool True om uppdateringen lyckades
     */
    public function updateArticleSchema(string $id, array $schema): bool
    {
        return $this->update($id, ['article_schema' => $schema]);
    }

    /**
     * Aktivera/inaktivera organisation
     *
     * @param string $id Organisation-ID
     * @param bool $active True för aktiv, false för inaktiv
     * @return bool True om uppdateringen lyckades
     */
    public function setActive(string $id, bool $active): bool
    {
        return $this->update($id, ['is_active' => $active]);
    }

    /**
     * Hämta alla units för en organisation
     *
     * @param string $id Organisation-ID
     * @return array Lista med units
     */
    public function getUnits(string $id): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM units WHERE organization_id = ? ORDER BY name",
            [$id]
        );
    }

    /**
     * Hämta antal units för en organisation
     *
     * @param string $id Organisation-ID
     * @return int Antal units
     */
    public function getUnitsCount(string $id): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM units WHERE organization_id = ?",
            [$id]
        );
    }

    /**
     * Hämta alla artiklar för en organisation
     *
     * @param string $id Organisation-ID
     * @param bool $activeOnly Endast aktiva artiklar
     * @return array Lista med artiklar
     */
    public function getArticles(string $id, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM articles WHERE organization_id = ?";
        $params = [$id];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY sku";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Hämta antal artiklar för en organisation
     *
     * @param string $id Organisation-ID
     * @return int Antal artiklar
     */
    public function getArticlesCount(string $id): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM articles WHERE organization_id = ?",
            [$id]
        );
    }

    /**
     * Hämta alla RFID-taggar som ägs av en organisation
     *
     * @param string $id Organisation-ID
     * @return array Lista med RFID-taggar
     */
    public function getRfids(string $id): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM rfids WHERE owner_org_id = ? ORDER BY created_at DESC",
            [$id]
        );
    }

    /**
     * Hämta antal RFID-taggar för en organisation
     *
     * @param string $id Organisation-ID
     * @return int Antal RFID-taggar
     */
    public function getRfidsCount(string $id): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM rfids WHERE owner_org_id = ?",
            [$id]
        );
    }
}
