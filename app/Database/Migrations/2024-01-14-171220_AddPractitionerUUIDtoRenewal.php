<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPractitionerUUIDtoRenewal extends Migration
{
    protected $fields = [
        "practitioner_uuid"=> [
            'type' => 'CHAR',
            'constraint' => 36,
            'null' => true,
            'default'=> null,
        ]
    ];
    public function up()
    {
        
        $this->forge->addColumn('practitioner_renewal', $this->fields);
    }

    public function down()
    {
        $this->forge->dropColumn('practitioner_renewal', $this->fields);
    }
}
