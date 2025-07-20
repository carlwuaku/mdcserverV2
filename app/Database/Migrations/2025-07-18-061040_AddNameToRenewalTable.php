<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNameToRenewalTable extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists("name", 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                "name" => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("region", 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                "region" => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("district", 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                "district" => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("email", 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                "email" => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("phone", 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                "phone" => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("country_of_practice", 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                "country_of_practice" => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                ]
            ]);
        }

        $this->forge->addKey('name', false);
        $this->forge->addKey('region', false);
        $this->forge->addKey('district', false);
        $this->forge->addKey('email', false);
        $this->forge->addKey('phone', false);
        $this->forge->addKey('country_of_practice', false);
        $this->forge->processIndexes('license_renewal');
    }

    public function down()
    {
        //
    }
}
