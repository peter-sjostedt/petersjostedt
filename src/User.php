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
 */

class User
{
    private Database $db;

    // Tillgängliga roller
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MODERATOR = 'moderator';

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
     * @return int|false Det nya användar-ID:t eller false vid fel
     */
    public function create(string $email, string $password, string $name, string $role = self::ROLE_USER): int|false
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

        // Hasha lösenordet säkert
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        try {
            $userId = $this->db->insert('users', [
                'email' => $email,
                'password' => $hashedPassword,
                'name' => $name,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

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
            "SELECT id, email, name, role, created_at, updated_at FROM users WHERE id = ?",
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
            "SELECT id, email, name, role, created_at, updated_at FROM users WHERE email = ?",
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
     * @return array Lista med användare
     */
    public function findAll(int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT id, email, name, role, created_at, updated_at FROM users ORDER BY created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Hämta användare efter roll
     *
     * @param string $role Roll att filtrera på
     * @return array Lista med användare
     */
    public function findByRole(string $role): array
    {
        return $this->db->fetchAll(
            "SELECT id, email, name, role, created_at, updated_at FROM users WHERE role = ? ORDER BY name",
            [$role]
        );
    }

    /**
     * Uppdatera användaruppgifter
     *
     * @param int $id Användar-ID
     * @param array $data Data att uppdatera (email, name, role)
     * @return bool True om uppdateringen lyckades
     */
    public function update(int $id, array $data): bool
    {
        // Tillåtna fält att uppdatera
        $allowed = ['email', 'name', 'role'];
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
     * @return int Antal användare
     */
    public function count(): int
    {
        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM users");
    }

    /**
     * Sök användare
     *
     * @param string $query Sökterm
     * @return array Matchande användare
     */
    public function search(string $query): array
    {
        $searchTerm = '%' . $query . '%';

        return $this->db->fetchAll(
            "SELECT id, email, name, role, created_at, updated_at
             FROM users
             WHERE name LIKE ? OR email LIKE ?
             ORDER BY name",
            [$searchTerm, $searchTerm]
        );
    }
}
