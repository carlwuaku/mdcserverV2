<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalDatesToLicenses extends Migration
{
    public function up()
    {
        $this->forge->addColumn('licenses', [
            'last_renewal_start' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
            ],
            'last_renewal_expiry' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
            ],
            'last_renewal_status' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null,
            ],
        ]);
    }

    public function down()
    {
        //
    }
}
