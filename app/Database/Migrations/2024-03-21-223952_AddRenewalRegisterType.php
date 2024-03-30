<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalRegisterType extends Migration
{
    public function up()
    {
        $this->forge->addColumn('practitioner_renewal', [
            'register_type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => 'Permanent',
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
