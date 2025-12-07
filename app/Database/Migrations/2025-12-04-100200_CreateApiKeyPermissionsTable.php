<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeyPermissionsTable extends Migration
{
    protected $tableName = "api_key_permissions";

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'api_key_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'comment' => 'Foreign key to api_keys table',
            ],
            'permission' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Permission name (e.g., View_Practitioners, Create_License)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('api_key_id');
        $this->forge->addUniqueKey(['api_key_id', 'permission']);

        $this->forge->addForeignKey('api_key_id', 'api_keys', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable($this->tableName, true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable($this->tableName, true);
    }
}
