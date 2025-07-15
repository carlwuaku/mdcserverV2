<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOtcmsTable extends Migration
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
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'premises_address' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
                'default' => null
            ],
            'picture' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null
            ],
            'town' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'coordinates' => [
                'type' => 'GEOMETRY',
                'null' => true,
                'default' => null
            ],
            'application_code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'date_of_birth' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'sex' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'qualification' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'maiden_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('name', false);
        $this->forge->addKey('license_number', false, true);
        $this->forge->addKey('application_code', false);
        $this->forge->addKey('sex', false);
        $this->forge->addKey('maiden_name', false);
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');
        $this->forge->addKey('id', true);

        $this->forge->createTable(
            'otcms',
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
