<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRolesPermissionsTable extends Migration
{
    protected $table = "role_permissions";
    public function up()
    {
        //
        $this->forge->addField([
            'role_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'permission_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ]
        ]);
 
        $this->forge->addKey(['role_id', 'permission_id'], TRUE);
        $this->forge->addForeignKey('role_id', 'roles', 'role_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'permission_id', 'CASCADE', 'CASCADE');
 
        $this->forge->createTable($this->table, TRUE);
    }

    public function down()
    {
        //
        $this->forge->dropTable($this->table);
    }
}
