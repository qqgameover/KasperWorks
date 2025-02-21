<?php namespace Kasperworks\Factory;

class MigrationFactory
{
    public static function createMigration(string $name): void
    {
        $timestamp = date("Y_m_d_His");
        $fileName = "{$timestamp}_{$name}.php";

        $migrationTemplate = "<?php

class {$name} {
    public function up(): void {
        // Write your 'up' migration logic here.
    }

    public function down(): void {
        // Write your 'down' migration logic here.
    }
}
";
        // Write the migration file to disk
        file_put_contents(
            __DIR__ . "/migrations/{$fileName}",
            $migrationTemplate
        );
        echo "Migration file created: migrations/{$fileName}\n";
    }
}
