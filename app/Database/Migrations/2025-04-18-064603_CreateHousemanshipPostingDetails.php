<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHousemanshipPostingDetails extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'posting_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'discipline' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'facility_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'facility_region' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'facility_details' => [
                'type' => 'JSON',
                'null' => true,
                'default' => null,
                'comment' => 'JSON data for facility details.  if not null it will be used to store the details of the facility. else join the facilities table to get the details.',
            ]
        ]);

        $this->forge->addKey('facility_name', false);
        $this->forge->addKey('facility_region', false);
        $this->forge->addKey('discipline', false);
        $this->forge->addKey('start_date', false);
        $this->forge->addKey('end_date', false);
        $this->forge->addForeignKey('facility_name', 'housemanship_facilities', 'name', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('posting_uuid', 'housemanship_postings', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->addKey('id', true);
        $this->forge->createTable('housemanship_postings_details');
    }

    public function down()
    {
        //
    }
}
