<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeHousemanshipApplicationsRegNumField extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('registration_number', 'housemanship_postings')) {
            // Modify the name field to be NOT NULL
            $this->forge->modifyColumn('housemanship_postings', [
                'registration_number' => [
                    'name' => 'license_number',
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false
                ]
            ]);
            $this->forge->addKey('license_number', false);
            $this->forge->processIndexes('housemanship_postings');
        }
    }

    public function down()
    {
        //
    }
}
