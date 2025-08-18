<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHousemanshipApplicationsDetailsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'application_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'discipline' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'first_choice' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'first_choice_region' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'second_choice' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'second_choice_region' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ]
        ]);

        $this->forge->addKey('application_uuid', false);
        $this->forge->addKey('first_choice', false);
        $this->forge->addKey('second_choice', false);
        $this->forge->addKey('discipline', false);
        $this->forge->addKey('first_choice_region', false);
        $this->forge->addKey('second_choice_region', false);
        $this->forge->addForeignKey('application_uuid', 'housemanship_postings_applications', 'uuid', 'CASCADE', 'CASCADE', 'posting_application_uuid');
        $this->forge->addForeignKey('first_choice', 'housemanship_facilities', 'name', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('second_choice', 'housemanship_facilities', 'name', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('discipline', 'housemanship_disciplines', 'name', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('first_choice_region', 'regions', 'name', 'CASCADE', 'RESTRICT', 'posting_first_choice_region');
        $this->forge->addForeignKey('second_choice_region', 'regions', 'name', 'CASCADE', 'RESTRICT', 'posting_second_choice_region');
        $this->forge->addKey('id', true);
        $this->forge->createTable('housemanship_postings_application_details', true);
    }

    public function down()
    {
        //
    }
}
