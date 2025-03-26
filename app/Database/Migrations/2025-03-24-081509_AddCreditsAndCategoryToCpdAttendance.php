<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreditsAndCategoryToCpdAttendance extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('credits', 'cpd_attendance')) {
            $this->forge->addColumn('cpd_attendance', [
                'credits' => [
                    'type' => 'DOUBLE',
                    'null' => false
                ],
            ]);
        }
        if (!$this->db->fieldExists('topic', 'cpd_attendance')) {
            $this->forge->addColumn('cpd_attendance', [
                'topic' => [
                    'type' => 'TEXT',
                    'null' => false
                ],
            ]);
        }
        if (!$this->db->fieldExists('category', 'cpd_attendance')) {
            $this->forge->addColumn('cpd_attendance', [
                'category' => [
                    'type' => 'TEXT',
                    'null' => false
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
