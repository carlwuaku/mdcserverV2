<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeExamCandidatePractitionerType extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('practitioner_type', 'exam_candidates')) {
            $this->forge->modifyColumn('exam_candidates', [
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
