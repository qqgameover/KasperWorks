<?php
require __DIR__ . "/../../vendor/autoload.php";

use Kasperworks\Migration;
use Kasperworks\Model;
use Kasperworks\Attributes\PrimaryKey;
use Kasperworks\Attributes\Unique;
use Kasperworks\Attributes\Index;
use Kasperworks\Attributes\ForeignKey;
use Kasperworks\Attributes\Required;

class MigrationGenerator
{
    private static function formatArrayForSchema(
        array $schema,
        int $indentLevel = 1
    ): string {
        $indent = str_repeat("    ", $indentLevel);
        $lines = ["["];

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $formattedValue = static::formatArrayForSchema(
                    $value,
                    $indentLevel + 1
                );
            } else {
                $formattedValue = var_export($value, true);
            }

            $lines[] = "{$indent}'{$key}' => {$formattedValue},";
        }

        $lines[] = str_repeat("    ", $indentLevel - 1) . "]";

        return implode("\n", $lines);
    }

    public static function generate(string $modelName): void
    {
        $modelClass = "Kasperworks\\Models\\{$modelName}";
        if (!class_exists($modelClass)) {
            die("Error: Model {$modelName} does not exist.\n");
        }

        $reflection = new \ReflectionClass($modelClass);

        // Ensure we only get fields from the model itself (not parent class)
        $properties = array_filter(
            $reflection->getProperties(),
            fn($prop) => $prop->getDeclaringClass()->getName() === $modelClass
        );

        /**
         * @var Model $modelClass
         * No idea why the LSP is complaining about this, it's correct somehow.
         */

        if (!property_exists($modelClass, "table")) {
            throw new \RuntimeException(
                "Model {$modelName} must have a static \$table property"
            );
        }
        $tableProperty = $reflection->getProperty("table");
        $tableName = $tableProperty->getValue(null);
        $schema = [];

        foreach ($properties as $property) {
            $column = $property->getName();

            if ($column == "table") {
                continue;
            }

            $attributes = $property->getAttributes();
            $type = $property->getType()?->getName();

            // Determine SQL Type
            $sqlType = match ($type) {
                "int" => "INT",
                "string" => "VARCHAR(255)",
                "float" => "FLOAT",
                "bool" => "TINYINT(1)",
                "DateTime" => "DATETIME",
                default => "TEXT",
            };

            $options = [];

            // Check if the field is another model (Foreign Key) and set the type accordingly to avoid ORM being too verbose
            if (class_exists($type) && is_subclass_of($type, Model::class)) {
                $sqlType = "INT";
                $typeReflection = new \ReflectionClass($type);
                $tableProp = $typeReflection->getProperty("table");
                $tablePropName = $tableProp->getValue(null);
                $options[] = "REFERENCES " . $tablePropName . "(id)";
            }

            foreach ($attributes as $attribute) {
                $attributeInstance = $attribute->newInstance();

                if (
                    $attributeInstance instanceof PrimaryKey &&
                    !in_array("PRIMARY KEY AUTO_INCREMENT", $options)
                ) {
                    $options[] = "PRIMARY KEY AUTO_INCREMENT";
                }
                if (
                    $attributeInstance instanceof Unique &&
                    !in_array("UNIQUE", $options)
                ) {
                    $options[] = "UNIQUE";
                }
                if (
                    $attributeInstance instanceof Index &&
                    !in_array("INDEX", $options)
                ) {
                    $options[] = "INDEX";
                }
                if (
                    $attributeInstance instanceof Required &&
                    !in_array("NOT NULL", $options)
                ) {
                    $options[] = "NOT NULL";
                }
                if ($attributeInstance instanceof ForeignKey) {
                    $sqlType = "INT";
                    $options[] = "REFERENCES {$attributeInstance->referencedTable}({$attributeInstance->referencedColumn})";

                    $schema[$column]["foreign"] = [
                        "table" => $attributeInstance->referencedTable,
                        "column" => $attributeInstance->referencedColumn,
                    ];
                }
            }

            $schema[$column] = [
                "type" => $sqlType,
                "options" => implode(" ", $options),
            ];
        }

        $migrationClass = "Create" . ucfirst($tableName) . "Table";
        $date = date("Y_m_d_His");
        $migrationFile =
            __DIR__ . "/../Migrations/{$date}_{$migrationClass}.php";

        $schemaExport = static::formatArrayForSchema($schema, 2);
        $migrationTemplate = <<<PHP
<?php

namespace Kasperworks\Migrations;

use Kasperworks\Migration;

class {$migrationClass} extends Migration
{
    protected string \$table = "{$tableName}";
    protected array \$schema = {$schemaExport};

    public function up(): void
    {
        \$this->createTable();
    }

    public function down(): void
    {
        \$this->dropTable();
    }
}
PHP;

        file_put_contents($migrationFile, $migrationTemplate);
        echo "Migration {$migrationClass} created successfully at {$migrationFile}\n";
    }
}

if (isset($argv[1])) {
    MigrationGenerator::generate($argv[1]);
}
