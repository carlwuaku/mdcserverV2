<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMcas extends Migration
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
            'id_type' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'id_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'qualification' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'nationality' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'workplace_address' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default' => null
            ],
            'location' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default' => null
            ],
            'training_institution' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('first_name', false, true);
        $this->forge->addKey('middle_name', false);
        $this->forge->addKey('last_name', false);
        $this->forge->addKey('sex', false);
        $this->forge->addKey('id_type', false);
        $this->forge->addKey('id_number', false);
        $this->forge->addKey('qualification', false);
        $this->forge->addKey('nationality', false);
        $this->forge->addKey('training_institution', false);
        $this->forge->addForeignKey('index_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');

        $this->forge->createTable(
            'mca',
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
