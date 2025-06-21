<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateExaminationsTable extends Migration
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
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'unique' => true
            ],
            'exam_type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'open_from' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'open_to' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'publish_scores' => [
                'type' => 'ENUM',
                'constraint' => [
                    'yes',
                    'no'
                ],
                'null' => true,
                'default' => 'no',
                'comment' => ''
            ],
            'publish_score_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
            ],
            'next_exam_month' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ],

            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),

            ],


        ]);

        $this->forge->addKey('exam_type', false);
        $this->forge->addKey('type', false);
        $this->forge->addKey('deleted_at', false);
        $this->forge->addKey('id', true);
        $this->forge->createTable('examinations', true);
    }

    public function down()
    {
        //
    }
}
