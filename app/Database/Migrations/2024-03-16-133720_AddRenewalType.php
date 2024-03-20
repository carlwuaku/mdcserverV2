<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalType extends Migration
{
    public function up()
    {
        $this->forge->addColumn('practitioner_renewal', [
            'practitioner_type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
