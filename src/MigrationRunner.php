<?php namespace Kasperworks;

use KasperWorks\Poworm;

class MigrationRunner
{
    protected \PDO $db;
    protected string $migrationsTable = "migrations";

    public function __construct()
    {
        $this->db = Poworm::getInstance()->db;
        $this->createMigrationsTableIfNotExists();
    }

    protected function createMigrationsTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($sql);
    }

    public function runMigration(Migration $migration, bool $apply = true): void
    {
        $migrationName = get_class($migration);
        $timestamp = date("Y_m_d_His");
        $teardownFileName =
            __DIR__ . "/teardowns/teardown_{$timestamp}_{$migrationName}.php";

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->migrationsTable} WHERE migration_name = ?"
        );
        $stmt->execute([$migrationName]);
        $migrationExists = $stmt->fetchColumn() > 0;

        if ($apply && !$migrationExists) {
            $migration->run(true);

            // Ensure class name matches filename (no special characters)
            $teardownClassName = "Teardown_" . str_replace(["\\", "/"], "_", $migrationName);

            $teardownTemplate = "<?php

class {$teardownClassName} {
    public function down(): void {
        // Place rollback logic here.
        {$migration->down()}
    }
}
";

            file_put_contents($teardownFileName, $teardownTemplate);
            echo "Teardown file created: {$teardownFileName}\n";

            $stmt = $this->db->prepare(
                "INSERT INTO {$this->migrationsTable} (migration_name) VALUES (?)"
            );
            $stmt->execute([$migrationName]);
            echo "Migration applied: {$migrationName}\n";
        } elseif (!$apply && $migrationExists) {
            $migration->run(false);
            $stmt = $this->db->prepare(
                "DELETE FROM {$this->migrationsTable} WHERE migration_name = ?"
            );
            $stmt->execute([$migrationName]);
            echo "Migration rolled back: {$migrationName}\n";
        } else {
            echo "Migration already applied: {$migrationName}\n";
        }
    }
}
