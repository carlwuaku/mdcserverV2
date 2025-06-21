<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateExaminationRegistrationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true
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
            'index_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'result' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => NULL,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),

            ],
            'registration_letter' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL,
            ],
            'result_letter' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL,
            ],
            'publish_result_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
                'comment' => 'The date from which the result will be published'
            ],


        ]);

        $this->forge->addKey('intern_code', false);//add one for intern_code,exam_id
        $this->forge->addKey(['exam_id', 'index_number'], false, true);
        $this->forge->addKey(['exam_id', 'intern_code'], false, true);
        $this->forge->addKey('exam_id', false);
        $this->forge->addKey('uuid', true);
        $this->forge->addForeignKey('exam_id', 'examinations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('intern_code', 'exam_candidates', 'intern_code', 'CASCADE', 'CASCADE');
        $this->forge->addKey('id', true);
        $this->forge->createTable('examination_registrations', true);
    }

    public function down()
    {
        //
    }
}
