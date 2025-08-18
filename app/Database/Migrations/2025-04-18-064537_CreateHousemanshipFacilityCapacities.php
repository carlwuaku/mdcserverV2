<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHousemanshipFacilityCapacities extends Migration
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
            'discipline' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'capacity' => [
                'type' => 'INT',
                'constraint' => 11,
            ]
        ]);
        $this->forge->addKey('facility_name', false);
        $this->forge->addKey('year', false);
        $this->forge->addKey('discipline', false);
        $this->forge->addKey('capacity', false);
        $this->forge->addForeignKey('facility_name', 'housemanship_facilities', 'name', 'CASCADE', 'CASCADE');
        $this->forge->addKey('id', true);
        $this->forge->createTable('housemanship_facility_capacities', true);
    }

    public function down()
    {
        $this->forge->dropTable('housemanship_facility_capacities');
    }
}
