<?php namespace Kasperworks;

use KasperWorks\Poworm;
use ReflectionClassConstant;
use Kasperworks\Attributes\ForeignKey;
use Kasperworks\Attributes\Index;

abstract class Migration
{
    protected \PDO $db;
    protected string $table;
    protected array $schema = [];
    protected bool $ran = false;

    public function __construct(string $table = "")
    {
        $this->db = Poworm::getInstance()->db;
        if ($table) {
            $this->table = $table;
        }
    }

    abstract public function up(): void;

    abstract public function down(): void;

    protected function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (";

        $columnDefs = [];
        $indexDefs = [];
        $foreignKeyDefs = [];

        foreach ($this->schema as $column => $details) {
            $columnSql = "{$column} {$details["type"]} {$details["options"]}";

            if (!isset($details["attributes"])) {
                $details["attributes"] = []; // Ensure it is an empty array
            }

            foreach ($details["attributes"] as $attribute) {
                if ($attribute instanceof Index) {
                    $indexDefs[] = "INDEX({$column})";
                }

                if ($attribute instanceof ForeignKey) {
                    $foreignKeyDefs[] = "FOREIGN KEY ({$column}) REFERENCES {$attribute->referencedTable}({$attribute->referencedColumn})";
                }
            }

            $columnDefs[] = $columnSql;
        }

        $sql .= implode(", ", $columnDefs);

        if (!empty($foreignKeyDefs)) {
            $sql .= ", " . implode(", ", $foreignKeyDefs);
        }

        if (!empty($indexDefs)) {
            $sql .= ", " . implode(", ", $indexDefs);
        }

        $sql .= ");";

        $this->db->exec($sql);
    }

    protected function dropTable(): void
    {
        $sql = "DROP TABLE IF EXISTS {$this->table};";
        $this->db->exec($sql);
    }

    protected function logMigration(string $migrationName): void
    {
        $timestamp = date("Y-m-d H:i:s");
        $sql = "INSERT INTO migrations (name, applied_at) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$migrationName, $timestamp]);
    }

    protected function isMigrationApplied(string $migrationName): bool
    {
        $sql = "SELECT COUNT(*) FROM migrations WHERE name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$migrationName]);
        return $stmt->fetchColumn() > 0;
    }

    public function run(bool $apply = true): void
    {
        $migrationName = get_class($this);

        if (!$this->isMigrationApplied($migrationName)) {
            if ($apply) {
                $this->up();
                $this->logMigration($migrationName);
            } else {
                $this->down();
            }
        } else {
            echo "Migration {$migrationName} has already been applied.\n";
        }

        $this->ran = true;
    }
}
