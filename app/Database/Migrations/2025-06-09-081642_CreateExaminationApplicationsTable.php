<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateExaminationApplicationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'intern_code' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'exam_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'application_status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'default' => 'Not Paid',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),

            ],


        ]);

        $this->forge->addKey('intern_code', false);
        $this->forge->addKey('exam_id', false);
        $this->forge->addKey('application_status', false);
        $this->forge->addForeignKey('exam_id', 'examinations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('intern_code', 'exam_candidates', 'intern_code', 'CASCADE', 'CASCADE');
        $this->forge->addKey('id', true);
        $this->forge->createTable('examination_applications', true);
    }

    public function down()
    {
        //
    }
}
