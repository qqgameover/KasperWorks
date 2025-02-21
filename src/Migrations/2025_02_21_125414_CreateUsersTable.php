<?php

namespace Kasperworks\Migrations;

use Kasperworks\Migration;

class CreateUsersTable extends Migration
{
    protected string $table = "users";
    protected array $schema = [
        'id' => [
            'type' => 'INT',
            'options' => 'PRIMARY KEY AUTO_INCREMENT',
        ],
        'email' => [
            'type' => 'VARCHAR(255)',
            'options' => 'UNIQUE NOT NULL',
        ],
        'password' => [
            'type' => 'VARCHAR(255)',
            'options' => 'NOT NULL',
        ],
    ];

    public function up(): void
    {
        $this->createTable();
    }

    public function down(): void
    {
        $this->dropTable();
    }
}
