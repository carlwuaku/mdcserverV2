<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermissionsTable extends Migration
{
    protected $tableName = "permissions";
    public function up()
    {
        $this->forge->addField([
            'permission_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 1000,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive', 'deleted'],
                'default' => 'active',
            ],
        ]);
  
        $this->forge->addPrimaryKey('permission_id');
        $this->forge->addUniqueKey(['name']);
  
        $this->forge->createTable($this->tableName, TRUE);
    }

    public function down()
    {
        //
        $this->forge->dropTable($this->tableName);
    }
}
