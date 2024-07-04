<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class HousemanshipFacilityMetadata extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'facility_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'name' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],


            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],


            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'modified_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name', false);
        $this->forge->addKey('facility_id', false);
        $this->forge->addKey('value', false);

        $this->forge->createTable('housemanship_facilities_metadata', true);

        $this->forge->addForeignKey('facility_id', 'housemanship_facilities', 'id', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('housemanship_facilities_metadata');
    }

    public function down()
    {
        //
    }
}
