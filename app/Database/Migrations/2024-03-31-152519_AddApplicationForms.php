<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddApplicationForms extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true,
            ],
            'practitioner_type' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'form_type' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'picture' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
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
            'application_code' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
                'unique' => true
            ],

            'form_data' => [
                'type' => 'JSON',
                'null' => false
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],

            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default' => null
            ],

            'qr_code' => [
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
        $this->forge->addKey('first_name', false);
        $this->forge->addKey('status', false);
        $this->forge->addKey('form_type', false);
        $this->forge->addKey('practitioner_type', false);
        $this->forge->addKey('last_name', false);
        $this->forge->addKey('middle_name', false);
        $this->forge->addKey('email', false);
        $this->forge->addKey('phone', false);

        $this->forge->createTable('application_forms', true);
    }

    public function down()
    {
        //
    }
}
