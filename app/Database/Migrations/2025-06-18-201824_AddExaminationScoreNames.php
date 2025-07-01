<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExaminationScoreNames extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('scores_names', 'examinations')) {
            $this->forge->addColumn('examinations', [
                'scores_names' => [
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
