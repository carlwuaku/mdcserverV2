<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExamLetterCriteraTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'letter_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false
            ],
            'field' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'The field in the exam candidates details that this criteria applies to, e.g., "nationality", "category", etc.'
            ],
            'value' => [
                'type' => 'JSON',//array of values
                'null' => false,
                'comment' => 'The value(s) that this criteria applies to, e.g., ["Kenyan", "Ugandan"] for. the value only needs to match one of the values in the array to be considered a match. if the value is 1, any non-null value will match. if 0, only null values will match. if you need to match 2 or more values repeat the field with the different values.',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ]


        ]);

        $this->forge->addKey('field', false);
        $this->forge->addKey('letter_id', false);
        $this->forge->addKey('value', false);
        $this->forge->addForeignKey('letter_id', 'examination_letter_templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey('id', true);
        $this->forge->createTable('examination_letter_template_criteria', true);

    }

    public function down()
    {
        //
    }
}
