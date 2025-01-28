<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCountryToLicenses extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('country_of_practice', 'licenses')) {
            $this->forge->addColumn('licenses', [
                'country_of_practice' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'default' => 'Ghana',
                    'constraint' => 255,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
