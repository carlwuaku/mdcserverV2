<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class AddTrainingInstitutions extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
                'unique' => true
            ],
            'location' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'contact_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'contact_position' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'district' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'default_limit' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
                'default' => 0
            ],
            'registration_start_month' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
                'default' => 1
            ],
            'registration_end_month' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
                'default' => 12
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'accredited_program' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('contact_name', false);
        $this->forge->addKey('contact_position', false);
        $this->forge->addKey('region', false);
        $this->forge->addKey('district', false);
        $this->forge->addKey('type', false);
        $this->forge->addKey('phone', false);
        $this->forge->addKey('email', false);
        $this->forge->addKey('status', false);
        $this->forge->addKey('category', false);
        $this->forge->addKey('accredited_program', false);
        $this->forge->createTable(
            'training_institutions',
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
