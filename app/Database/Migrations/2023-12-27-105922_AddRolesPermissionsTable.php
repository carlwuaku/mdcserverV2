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
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('role_id', 'roles', 'role_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'permission_id', 'CASCADE', 'RESTRICT');

        $this->forge->createTable($this->table, TRUE, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
        $this->forge->dropTable($this->table);
    }
}
