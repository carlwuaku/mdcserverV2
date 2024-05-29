<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\RawSql;

use CodeIgniter\Database\Migration;

class CreateApplicationFormTemplateTable extends Migration
{
    public function up()
    {
        [
            'uuid',
            'form_name',
            'description',
            'guidelines',
            'header',
            'footer',
            'data',
            'open_from',
            'open_to',
            'created_on',
            'updated_at',
            'deleted_at'
        ];
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
            'form_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'guidelines' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'header' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'footer' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'data' => [
                'type' => 'JSON',
                'null' => false
            ],
            'open_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],

            'close_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'on_submit_email' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],

            'on_submit_message' => [
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
        $this->forge->addKey('form_name', false);
        $this->forge->addKey('open_date', false);
        $this->forge->addKey('close_date', false);

        $this->forge->createTable('application_form_templates', true);
    }

    public function down()
    {
        //
    }
}
