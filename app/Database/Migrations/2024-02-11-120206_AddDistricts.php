<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDistricts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'district' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'unique' => false,
            ],
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'region_id' => [
                'type' => 'INT',
                'constraint' => 9,
                'null' => false
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('region', false, false, 'region_key');
        $this->forge->addForeignKey('region', 'regions', 'name', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('districts', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('districts');
    }
}
