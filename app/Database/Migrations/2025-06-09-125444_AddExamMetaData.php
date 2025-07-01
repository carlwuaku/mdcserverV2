<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExamMetaData extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('metadata', 'examinations')) {
            $this->forge->addColumn('examinations', [
                'metadata' => [
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
