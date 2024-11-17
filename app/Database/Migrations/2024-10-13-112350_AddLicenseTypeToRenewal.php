<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseTypeToRenewal extends Migration
{
    public function up()
    {
        $this->forge->addColumn('license_renewal', [
            "license_type" => [
                'type' => 'TEXT',
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
