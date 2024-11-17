<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseUUIDToRenewal extends Migration
{
    public function up()
    {
        $this->forge->addColumn('license_renewal', [
            "license_uuid" => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'default' => null,
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
