<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExamRegistrationScores extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('scores', 'examination_registrations')) {
            $this->forge->addColumn('examination_registrations', [
                'scores' => [
                    'type' => 'JSON',
                    'null' => true,
                    'default' => NULL,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
