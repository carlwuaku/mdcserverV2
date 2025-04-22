<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateHousemanshipFacilitiesNameFieldType extends Migration
{
    public function up()
    {
        // Check if the table exists
        if ($this->db->tableExists('housemanship_facilities')) {
            // Check if the name field exists
            if ($this->db->fieldExists('name', 'housemanship_facilities')) {
                // Modify the name field to be NOT NULL
                $this->forge->modifyColumn('housemanship_facilities', [
                    'name' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => false,
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        //
    }
}
