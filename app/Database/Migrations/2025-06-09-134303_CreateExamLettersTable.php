<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExamLettersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'exam_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['registration', 'fail', 'pass'],
                'null' => false
            ],
            'content' => [
                'type' => 'TEXT',
                'null' => false,
                'default' => NULL,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ]


        ]);

        $this->forge->addKey('type', false);
        $this->forge->addKey('exam_id', false);
        $this->forge->addKey('name', false);
        $this->forge->addForeignKey('exam_id', 'examinations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey('id', true);
        $this->forge->createTable('examination_letter_templates', true);

    }

    public function down()
    {
        //
    }
}
