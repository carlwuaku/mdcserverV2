<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePractitionerType extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('practitioner_type', 'practitioners')) {
            $this->forge->modifyColumn('practitioners', [
                'practitioner_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
