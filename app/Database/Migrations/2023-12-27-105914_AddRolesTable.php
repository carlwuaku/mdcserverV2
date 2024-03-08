<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRolesTable extends Migration
{
    protected $table = "roles";
    public function up()
    {
        //
        $this->forge->addField([
            'role_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'role_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 1000,
                'null' => true,
            ],
            'default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'can_delete' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'login_destination' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => '/',
            ],
            'default_context' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => 'content',
                'null' => true,
            ],
            'deleted' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 0,
            ],
        ]);
   
        $this->forge->addPrimaryKey('role_id');
        $this->forge->addUniqueKey(['role_name']);
   
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
