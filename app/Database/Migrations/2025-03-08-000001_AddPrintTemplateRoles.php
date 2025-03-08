<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintTemplateRoles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'template_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
            ],
            'role_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['template_uuid', 'role_name']);
        $this->forge->createTable('print_template_roles');
    }

    public function down()
    {
        $this->forge->dropTable('print_template_roles');
    }
} 