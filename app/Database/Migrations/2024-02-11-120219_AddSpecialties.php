<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSpecialties extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
                'unique' => true,
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name', false, false, 'specialty');

        $this->forge->createTable('specialties', true);
    }

    public function down()
    {
        $this->forge->dropTable('specialties');
    }
}
