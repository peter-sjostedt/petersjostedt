<?php
/**
 * Migration - Databasmigrering
 *
 * Hanterar versionskontroll av databasscheman.
 * Kör SQL-filer i ordning och håller reda på vilka som körts.
 */

class Migration
{
    private static ?Migration $instance = null;
    private Database $db;
    private Logger $logger;
    private string $migrationsDir;
    private string $migrationsTable = 'migrations';

    /**
     * Privat konstruktor - använd getInstance()
     */
    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->migrationsDir = __DIR__ . '/../database/migrations';

        // Skapa migrations-mapp om den inte finns
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
        }

        // Skapa migrations-tabell om den inte finns
        $this->ensureMigrationsTable();
    }

    /**
     * Hämta instansen (singleton)
     */
    public static function getInstance(): Migration
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Säkerställ att migrations-tabellen finns
     */
    private function ensureMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->db->execute($sql);
        } catch (Exception $e) {
            $this->logger->error('MIGRATION_TABLE_ERROR', null, $e->getMessage());
        }
    }

    /**
     * Kör alla pending migrations
     */
    public function migrate(): array
    {
        $result = [
            'success' => true,
            'executed' => [],
            'skipped' => [],
            'errors' => []
        ];

        $pendingMigrations = $this->getPendingMigrations();

        if (empty($pendingMigrations)) {
            $result['message'] = 'Inga migrations att köra';
            return $result;
        }

        $batch = $this->getNextBatch();

        foreach ($pendingMigrations as $migration) {
            try {
                $this->executeMigration($migration, 'up');
                $this->recordMigration($migration, $batch);
                $result['executed'][] = $migration;
                $this->logger->info('MIGRATION_EXECUTED', null, "Migration kördes: {$migration}");
            } catch (Exception $e) {
                $result['success'] = false;
                $result['errors'][] = [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ];
                $this->logger->error('MIGRATION_ERROR', null, "Migration misslyckades: {$migration} - {$e->getMessage()}");
                break; // Stoppa vid första felet
            }
        }

        return $result;
    }

    /**
     * Rulla tillbaka senaste batch
     */
    public function rollback(): array
    {
        $result = [
            'success' => true,
            'rolled_back' => [],
            'errors' => []
        ];

        $lastBatch = $this->getLastBatch();

        if ($lastBatch === null) {
            $result['message'] = 'Inga migrations att rulla tillbaka';
            return $result;
        }

        $migrations = $this->getMigrationsInBatch($lastBatch);

        // Kör i omvänd ordning
        foreach (array_reverse($migrations) as $migration) {
            try {
                $this->executeMigration($migration, 'down');
                $this->removeMigration($migration);
                $result['rolled_back'][] = $migration;
                $this->logger->info('MIGRATION_ROLLED_BACK', null, "Migration återställd: {$migration}");
            } catch (Exception $e) {
                $result['success'] = false;
                $result['errors'][] = [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ];
                $this->logger->error('MIGRATION_ROLLBACK_ERROR', null, "Återställning misslyckades: {$migration} - {$e->getMessage()}");
                break;
            }
        }

        return $result;
    }

    /**
     * Visa status för migrations
     */
    public function status(): array
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();

        $status = [];

        foreach ($allMigrations as $migration) {
            $executed = isset($executedMigrations[$migration]);
            $status[] = [
                'migration' => $migration,
                'executed' => $executed,
                'batch' => $executed ? $executedMigrations[$migration]['batch'] : null,
                'executed_at' => $executed ? $executedMigrations[$migration]['executed_at'] : null
            ];
        }

        return $status;
    }

    /**
     * Skapa en ny migration-fil
     */
    public function create(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.sql";
        $filepath = $this->migrationsDir . '/' . $filename;

        $template = "-- Migration: {$filename}
-- Description: {$name}
-- Created: " . date('Y-m-d H:i:s') . "

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================



-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================


";

        file_put_contents($filepath, $template);

        $this->logger->info('MIGRATION_CREATED', null, "Migration skapad: {$filename}");

        return $filepath;
    }

    /**
     * Exekvera en migration (upp eller ner)
     */
    private function executeMigration(string $migration, string $direction): void
    {
        $filepath = $this->migrationsDir . '/' . $migration;

        if (!file_exists($filepath)) {
            throw new Exception("Migration-fil hittades inte: {$migration}");
        }

        $content = file_get_contents($filepath);
        $sql = $this->extractSQL($content, $direction);

        if (empty(trim($sql))) {
            throw new Exception("Ingen {$direction} SQL hittades i {$migration}");
        }

        // Kör SQL
        $statements = $this->splitSQL($sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;

            $this->db->execute($statement);
        }
    }

    /**
     * Extrahera SQL från migration-fil (UP eller DOWN)
     */
    private function extractSQL(string $content, string $direction): string
    {
        $direction = strtoupper($direction);

        // Hitta rätt sektion
        $pattern = "/--\s*=+\s*\n--\s*{$direction}:.*?\n--\s*=+\s*\n(.*?)(?=--\s*=+|$)/s";

        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Dela upp SQL i separata statements
     */
    private function splitSQL(string $sql): array
    {
        // Ta bort kommentarer
        $sql = preg_replace('/--.*$/m', '', $sql);

        // Dela på semikolon (men inte inom strängar)
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = null;

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];

            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($i === 0 || $sql[$i-1] !== '\\')) {
                $inString = false;
            } elseif (!$inString && $char === ';') {
                if (!empty(trim($current))) {
                    $statements[] = $current;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (!empty(trim($current))) {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * Hämta alla migration-filer
     */
    private function getAllMigrationFiles(): array
    {
        $files = glob($this->migrationsDir . '/*.sql');
        $migrations = array_map('basename', $files);
        sort($migrations);
        return $migrations;
    }

    /**
     * Hämta pending migrations (ej körda)
     */
    private function getPendingMigrations(): array
    {
        $all = $this->getAllMigrationFiles();
        $executed = array_keys($this->getExecutedMigrations());
        return array_diff($all, $executed);
    }

    /**
     * Hämta körda migrations
     */
    private function getExecutedMigrations(): array
    {
        $result = $this->db->fetchAll("SELECT migration, batch, executed_at FROM {$this->migrationsTable} ORDER BY id");

        $migrations = [];
        foreach ($result as $row) {
            $migrations[$row['migration']] = [
                'batch' => (int)$row['batch'],
                'executed_at' => $row['executed_at']
            ];
        }

        return $migrations;
    }

    /**
     * Hämta migrations i en batch
     */
    private function getMigrationsInBatch(int $batch): array
    {
        $result = $this->db->fetchAll(
            "SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id",
            [$batch]
        );

        return array_column($result, 'migration');
    }

    /**
     * Hämta nästa batch-nummer
     */
    private function getNextBatch(): int
    {
        $result = $this->db->fetchOne("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        return ((int)($result['max_batch'] ?? 0)) + 1;
    }

    /**
     * Hämta senaste batch-nummer
     */
    private function getLastBatch(): ?int
    {
        $result = $this->db->fetchOne("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        $batch = $result['max_batch'] ?? null;
        return $batch ? (int)$batch : null;
    }

    /**
     * Spara att migration körts
     */
    private function recordMigration(string $migration, int $batch): void
    {
        $this->db->insert($this->migrationsTable, [
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    /**
     * Ta bort migration från logg
     */
    private function removeMigration(string $migration): void
    {
        $this->db->delete($this->migrationsTable, 'migration = ?', [$migration]);
    }

    /**
     * Återställ alla migrations (FARLIGT!)
     */
    public function reset(): array
    {
        $result = [
            'success' => true,
            'rolled_back' => [],
            'errors' => []
        ];

        while ($this->getLastBatch() !== null) {
            $rollbackResult = $this->rollback();

            if (!$rollbackResult['success']) {
                $result['success'] = false;
                $result['errors'] = array_merge($result['errors'], $rollbackResult['errors']);
                break;
            }

            $result['rolled_back'] = array_merge($result['rolled_back'], $rollbackResult['rolled_back']);
        }

        return $result;
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
