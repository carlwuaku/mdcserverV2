<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermissionNameToRolePermissions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('role_permissions', [
            'permission' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ],
            'role' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ]
        ]);

        //add the fk
        $this->forge->addForeignKey('permission', 'permissions', 'name', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('role_permissions');
    }

    public function down()
    {
        //
    }
}
