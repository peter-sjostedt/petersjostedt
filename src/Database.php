<?php
/**
 * Database - PDO-wrapper med prepared statements
 *
 * Säker databashantering med:
 * - Singleton-mönster för en anslutning
 * - Prepared statements för alla queries
 * - Automatisk felhantering
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    /**
     * Privat konstruktor - använd getInstance()
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Hämta databasinstansen (singleton)
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Anslut till databasen
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                // Kasta exceptions vid fel
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Returnera associativa arrayer
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Använd riktiga prepared statements (inte emulerade)
                PDO::ATTR_EMULATE_PREPARES => false,
                // Persistent anslutning för bättre prestanda
                PDO::ATTR_PERSISTENT => true
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            // Logga felet men visa inte känslig info
            error_log('Databasanslutning misslyckades: ' . $e->getMessage());
            throw new Exception('Kunde inte ansluta till databasen');
        }
    }

    /**
     * Hämta PDO-instansen direkt (för avancerade queries)
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Kör en SELECT-query och returnera alla rader
     *
     * @param string $sql SQL-query med placeholders
     * @param array $params Parametrar för prepared statement
     * @return array Alla matchande rader
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Kör en SELECT-query och returnera en rad
     *
     * @param string $sql SQL-query med placeholders
     * @param array $params Parametrar för prepared statement
     * @return array|null En rad eller null om ingen träff
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Kör en SELECT-query och returnera ett enda värde
     *
     * @param string $sql SQL-query med placeholders
     * @param array $params Parametrar för prepared statement
     * @return mixed Värdet eller null
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Kör INSERT, UPDATE eller DELETE
     *
     * @param string $sql SQL-query med placeholders
     * @param array $params Parametrar för prepared statement
     * @return int Antal påverkade rader
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Infoga en rad och returnera det nya ID:t
     *
     * @param string $table Tabellnamn
     * @param array $data Associativ array med kolumn => värde
     * @return int Det nya radens ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Uppdatera rader i en tabell
     *
     * @param string $table Tabellnamn
     * @param array $data Associativ array med kolumn => värde
     * @param string $where WHERE-villkor med placeholders
     * @param array $whereParams Parametrar för WHERE
     * @return int Antal uppdaterade rader
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $params = array_merge(array_values($data), $whereParams);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Ta bort rader från en tabell
     *
     * @param string $table Tabellnamn
     * @param string $where WHERE-villkor med placeholders
     * @param array $params Parametrar för WHERE
     * @return int Antal borttagna rader
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Starta en transaktion
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Bekräfta transaktionen
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Ångra transaktionen
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
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
