<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class FacilitiesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'license_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'name' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'town' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'suburb' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'business_type' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'house_number' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'coordinates' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ]
        ]);

        $this->forge->addPrimaryKey('id');

        $this->forge->createTable(
            'facilities',
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
