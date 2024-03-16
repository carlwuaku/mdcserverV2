<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPractitionerRenewalFields extends Migration
{
    public function up()
    {
        //add fields last_renewal_start, last_renewal_expiry, last_renewal_status
        $this->forge->addColumn('practitioners', [
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
