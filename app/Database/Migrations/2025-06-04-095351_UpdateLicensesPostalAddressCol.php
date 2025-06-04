<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateLicensesPostalAddressCol extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('postal_address', 'licenses')) {
            // Modify the name field to be NOT NULL
            $this->forge->modifyColumn('licenses', [
                'postal_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => '500',
                    'null' => true,
                    'default' => null
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
