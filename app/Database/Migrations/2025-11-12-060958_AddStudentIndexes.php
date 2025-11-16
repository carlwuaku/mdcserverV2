<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStudentIndexes extends Migration
{
    public function up()
    {

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'index_number' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
                'unique' => true
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
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
                'null' => false
            ],
            'date_of_birth' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'sex' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false,
            ],
            'student_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'nationality' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'training_institution' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'year' => [
                'type' => 'YEAR',
                'constraint' => '4',
                'null' => true,
                'default' => null
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('first_name', false);
        $this->forge->addKey('middle_name', false);
        $this->forge->addKey('last_name', false);
        $this->forge->addKey('sex', false);
        $this->forge->addKey('student_id', false);
        $this->forge->addKey('nationality', false);
        $this->forge->addKey('training_institution', false);
        $this->forge->addKey('year', false);
        $this->forge->addForeignKey('index_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('training_institution', 'training_institutions', 'name', 'CASCADE', 'RESTRICT');

        $this->forge->createTable(
            'student_indexes',
            true,
            [
                'ENGINE' => 'InnoDB',
            ]
        );
    }

    public function down()
    {
        //
    }
}
