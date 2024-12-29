<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoleNameToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('role_name', 'users')) {
            $this->forge->addColumn("users", [
                'role_name' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'default' => null,
                    'constraint' => 255,
                ],
            ]);
        }
        $this->forge->addForeignKey('role_name', 'roles', 'role_name', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('users');
        $this->db->query('UPDATE users u JOIN roles r ON u.role_id = r.role_id SET u.role_name = r.role_name');


    }

    public function down()
    {
        //
    }
}
