<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHousemanshipFacilityAvailability extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'facility_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'year' => [
                'type' => 'YEAR',
                'null' => true,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'available' => [
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => false,
            ]
        ]);

        $this->forge->addKey('facility_name', false);
        $this->forge->addKey('year', false);
        $this->forge->addKey('category', false);
        $this->forge->addKey('available', false);
        $this->forge->addForeignKey('facility_name', 'housemanship_facilities', 'name', 'CASCADE', 'CASCADE');

        $this->forge->addKey('id', true);
        $this->forge->createTable('housemanship_facility_availability');
    }

    public function down()
    {
        //
    }
}
