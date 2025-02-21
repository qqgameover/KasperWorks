<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use Kasperworks\Poworm;

class MigrationRunner
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Poworm::getInstance()->db;
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    private function getAppliedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function markMigrationAsApplied(string $migration): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO migrations (migration) VALUES (:migration)"
        );
        $stmt->execute(["migration" => $migration]);
    }

    public function run(): void
    {
        $appliedMigrations = $this->getAppliedMigrations();
        $migrationFiles = glob(__DIR__ . "/../Migrations/*.php");

        sort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $migrationClass = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($migrationClass, $appliedMigrations)) {
                echo "Skipping {$migrationClass} (already applied)\n";
                continue;
            }

            $classNameOnly = preg_replace('/^\d+_\d+_\d+_\d+_/', '', $migrationClass);


            $className = "Kasperworks\\Migrations\\$classNameOnly";

            if (!class_exists($className, false)) { // Check before requiring
                    require_once $file;
            }

            if (!class_exists($className)) {
                echo "Error: Migration class {$className} not found.\n";
                continue;
            }


            $migration = new $className();
            $migration->up();

            $this->markMigrationAsApplied($migrationClass);
            echo "Applied migration: {$migrationClass}\n";
        }

        echo "✅ All migrations have been executed!\nLong live this garbage code!";
    }

    public function rollback(): void
    {
        $stmt = $this->db->query(
            "SELECT migration FROM migrations ORDER BY id DESC LIMIT 1"
        );
        $lastMigration = $stmt->fetchColumn();

        if (!$lastMigration) {
            echo "No migrations to rollback.\n";
            return;
        }

        $className = "Kasperworks\\Migrations\\$lastMigration";
        if (!class_exists($className)) {
            echo "Error: Migration class {$className} not found.\n";
            return;
        }

        $migration = new $className();
        $migration->down();

        $stmt = $this->db->prepare(
            "DELETE FROM migrations WHERE migration = :migration"
        );
        $stmt->execute(["migration" => $lastMigration]);

        echo "Rolled back migration: {$lastMigration}\n";
    }
}

$runner = new MigrationRunner();
$runner->run();
