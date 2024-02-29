<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegions extends Migration
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
            ],
            'code'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default'=> null,
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name', false, false, 'region_name');

        $this->forge->createTable('regions', true);
    }

    public function down()
    {
        $this->forge->dropTable('regions');
    }
}
