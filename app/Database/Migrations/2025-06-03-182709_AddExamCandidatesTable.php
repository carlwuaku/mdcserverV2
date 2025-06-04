<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddExamCandidatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'middle_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'date_of_birth' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'intern_code' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
                'unique' => true
            ],
            'sex' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'registration_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'nationality' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null
            ],
            'qualification' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null
            ],
            'training_institution' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null
            ],
            'qualification_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'state' => [
                'type' => 'ENUM',
                'constraint' => ['Apply for examination', 'Apply for migration', 'Migrated'],
                'null' => false,
                'default' => 'Apply for examination'
            ],
            'specialty' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'practitioner_type' => [
                'type' => 'ENUM',
                'constraint' => ['Doctor', 'Physician Assistant'],
                'null' => true,
                'default' => null
            ],
            'number_of_exams' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
                'default' => null,
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('first_name', false);
        $this->forge->addKey('last_name', false);
        $this->forge->addKey('middle_name', false);
        $this->forge->addKey('category', false);
        $this->forge->addKey('specialty', false);
        $this->forge->addKey('state', false);
        $this->forge->addKey('practitioner_type', false);

        $this->forge->createTable('exam_candidates', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
    }
}
