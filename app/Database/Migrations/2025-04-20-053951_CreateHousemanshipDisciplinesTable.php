<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHousemanshipDisciplinesTable extends Migration
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
        $this->forge->addKey('name', false, false, 'discipline_name');

        $this->forge->createTable('housemanship_disciplines', true, );
    }

    public function down()
    {
        //
    }
}
