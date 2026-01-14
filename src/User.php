<?php
/**
 * User - Användarhantering
 *
 * Hanterar:
 * - Skapa nya användare
 * - Hämta användare (via ID eller e-post)
 * - Uppdatera användaruppgifter
 * - Ta bort användare
 * - Verifiera lösenord
 * - Rollhantering
 * - Organisationsfiltrering
 */

class User
{
    private Database $db;

    // Tillgängliga roller (matchar ENUM i databasen)
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ORG_ADMIN = 'org_admin';

    /**
     * Konstruktor - hämta databasinstans
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Skapa en ny användare
     *
     * @param string $email E-postadress
     * @param string $password Lösenord (i klartext, hashas automatiskt)
     * @param string $name Användarens namn
     * @param string $role Roll (standard: user)
     * @param string|null $organizationId Organisation ID (krävs för org_admin)
     * @return int|false Det nya användar-ID:t eller false vid fel
     */
    public function create(string $email, string $password, string $name, string $role = self::ROLE_USER, ?string $organizationId = null): int|false
    {
        // Validera e-post
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Kontrollera att e-posten inte redan finns
        if ($this->findByEmail($email)) {
            return false;
        }

        // Validera lösenordsstyrka
        if (strlen($password) < 8) {
            return false;
        }

        // org_admin måste ha organization_id
        if ($role === self::ROLE_ORG_ADMIN && empty($organizationId)) {
            return false;
        }

        // Hasha lösenordet säkert
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        try {
            $data = [
                'email' => $email,
                'password' => $hashedPassword,
                'name' => $name,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Lägg till organization_id om det finns
            if ($organizationId !== null) {
                $data['organization_id'] = $organizationId;
            }

            $userId = $this->db->insert('users', $data);

            return $userId;

        } catch (Exception $e) {
            error_log('Fel vid skapande av användare: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hämta användare via ID
     *
     * @param int $id Användar-ID
     * @return array|null Användardata eller null
     */
    public function findById(int $id): ?array
    {
        $user = $this->db->fetchOne(
            "SELECT id, email, name, role, organization_id, created_at, updated_at FROM users WHERE id = ?",
            [$id]
        );

        return $user;
    }

    /**
     * Hämta användare via e-post
     *
     * @param string $email E-postadress
     * @return array|null Användardata eller null
     */
    public function findByEmail(string $email): ?array
    {
        $user = $this->db->fetchOne(
            "SELECT id, email, name, role, organization_id, created_at, updated_at FROM users WHERE email = ?",
            [$email]
        );

        return $user;
    }

    /**
     * Hämta användare med lösenordshash (för inloggning)
     *
     * @param string $email E-postadress
     * @return array|null Användardata inkl. lösenord eller null
     */
    private function findByEmailWithPassword(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    /**
     * Hämta alla användare
     *
     * @param int $limit Max antal (0 = alla)
     * @param int $offset Startposition
     * @param string|null $organizationId Filtrera på organisation (null = alla)
     * @param string $sortBy Kolumn att sortera på
     * @param string $sortOrder ASC eller DESC
     * @return array Lista med användare
     */
    public function findAll(int $limit = 0, int $offset = 0, ?string $organizationId = null, string $sortBy = 'name', string $sortOrder = 'ASC'): array
    {
        // Validera sorteringskolumn
        $allowedSort = ['name', 'email', 'role', 'organization_name'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'name';
        }
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        // Mappa organization_name till rätt kolumn
        $orderColumn = $sortBy === 'organization_name' ? 'o.name' : "u.{$sortBy}";

        $sql = "SELECT u.id, u.email, u.name, u.role, u.organization_id, u.created_at, u.updated_at,
                       o.name as organization_name
                FROM users u
                LEFT JOIN organizations o ON u.organization_id = o.id";
        $params = [];

        if ($organizationId !== null) {
            // SYSTEM-organisationen visar användare utan organisation (admins)
            if ($organizationId === 'SYSTEM') {
                $sql .= " WHERE u.organization_id IS NULL";
            } else {
                $sql .= " WHERE u.organization_id = ?";
                $params[] = $organizationId;
            }
        }

        $sql .= " ORDER BY {$orderColumn} {$sortOrder}";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Hämta användare efter roll
     *
     * @param string $role Roll att filtrera på
     * @param string|null $organizationId Filtrera på organisation (null = alla)
     * @return array Lista med användare
     */
    public function findByRole(string $role, ?string $organizationId = null): array
    {
        $sql = "SELECT id, email, name, role, organization_id, created_at, updated_at FROM users WHERE role = ?";
        $params = [$role];

        if ($organizationId !== null) {
            $sql .= " AND organization_id = ?";
            $params[] = $organizationId;
        }

        $sql .= " ORDER BY name";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Hämta användare för en organisation
     *
     * @param string $organizationId Organisation ID
     * @return array Lista med användare
     */
    public function findByOrganization(string $organizationId): array
    {
        return $this->db->fetchAll(
            "SELECT id, email, name, role, organization_id, created_at, updated_at FROM users WHERE organization_id = ? ORDER BY name",
            [$organizationId]
        );
    }

    /**
     * Uppdatera användaruppgifter
     *
     * @param int $id Användar-ID
     * @param array $data Data att uppdatera (email, name, role, organization_id)
     * @return bool True om uppdateringen lyckades
     */
    public function update(int $id, array $data): bool
    {
        // Tillåtna fält att uppdatera
        $allowed = ['email', 'name', 'role', 'organization_id'];
        $updateData = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        // Validera e-post om den uppdateras
        if (isset($updateData['email'])) {
            if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            // Kontrollera att e-posten inte redan används av annan användare
            $existing = $this->findByEmail($updateData['email']);
            if ($existing && $existing['id'] !== $id) {
                return false;
            }
        }

        // Lägg till updated_at
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $affected = $this->db->update('users', $updateData, 'id = ?', [$id]);
            return $affected > 0;

        } catch (Exception $e) {
            error_log('Fel vid uppdatering av användare: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Uppdatera lösenord
     *
     * @param int $id Användar-ID
     * @param string $newPassword Nytt lösenord (i klartext)
     * @return bool True om uppdateringen lyckades
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        // Validera lösenordsstyrka
        if (strlen($newPassword) < 8) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        try {
            $affected = $this->db->update(
                'users',
                [
                    'password' => $hashedPassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$id]
            );

            return $affected > 0;

        } catch (Exception $e) {
            error_log('Fel vid uppdatering av lösenord: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ta bort användare
     *
     * @param int $id Användar-ID
     * @return bool True om borttagningen lyckades
     */
    public function delete(int $id): bool
    {
        try {
            // Ta först bort relaterade sessioner
            $this->db->delete('sessions', 'user_id = ?', [$id]);

            // Ta bort relaterade loggar (eller sätt user_id till NULL)
            $this->db->execute(
                "UPDATE logs SET user_id = NULL WHERE user_id = ?",
                [$id]
            );

            // Ta bort användaren
            $affected = $this->db->delete('users', 'id = ?', [$id]);

            return $affected > 0;

        } catch (Exception $e) {
            error_log('Fel vid borttagning av användare: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifiera lösenord och returnera användare om korrekt
     *
     * @param string $email E-postadress
     * @param string $password Lösenord att verifiera
     * @return array|null Användardata (utan lösenord) eller null
     */
    public function verifyPassword(string $email, string $password): ?array
    {
        $user = $this->findByEmailWithPassword($email);

        if (!$user) {
            // Kör ändå password_verify för att förhindra timing-attacker
            password_verify($password, '$argon2id$v=19$m=65536,t=4,p=3$dummy$hash');
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        // Kontrollera om hashen behöver uppdateras
        if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
            $this->updatePassword($user['id'], $password);
        }

        // Ta bort lösenordet innan vi returnerar
        unset($user['password']);

        return $user;
    }

    /**
     * Autentisera användare (alias för verifyPassword)
     *
     * @param string $email E-postadress
     * @param string $password Lösenord
     * @return array|null Användardata eller null
     */
    public function authenticate(string $email, string $password): ?array
    {
        return $this->verifyPassword($email, $password);
    }

    /**
     * Kontrollera om användare har en specifik roll
     *
     * @param int $userId Användar-ID
     * @param string $role Roll att kontrollera
     * @return bool True om användaren har rollen
     */
    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->findById($userId);
        return $user && $user['role'] === $role;
    }

    /**
     * Kontrollera om användare är admin
     *
     * @param int $userId Användar-ID
     * @return bool True om admin
     */
    public function isAdmin(int $userId): bool
    {
        return $this->hasRole($userId, self::ROLE_ADMIN);
    }

    /**
     * Räkna totalt antal användare
     *
     * @param string|null $organizationId Filtrera på organisation (null = alla)
     * @return int Antal användare
     */
    public function count(?string $organizationId = null): int
    {
        if ($organizationId !== null) {
            return (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE organization_id = ?",
                [$organizationId]
            );
        }
        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM users");
    }

    /**
     * Sök användare
     *
     * @param string $query Sökterm
     * @param string|null $organizationId Filtrera på organisation (null = alla)
     * @return array Matchande användare
     */
    public function search(string $query, ?string $organizationId = null): array
    {
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT id, email, name, role, organization_id, created_at, updated_at
                FROM users
                WHERE (name LIKE ? OR email LIKE ?)";
        $params = [$searchTerm, $searchTerm];

        if ($organizationId !== null) {
            $sql .= " AND organization_id = ?";
            $params[] = $organizationId;
        }

        $sql .= " ORDER BY name";

        return $this->db->fetchAll($sql, $params);
    }
}
