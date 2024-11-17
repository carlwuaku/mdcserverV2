<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class VerificationLogs extends Migration
{
    public function up()
    {


        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
                'null' => false,
            ],
            'verification_token' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => false
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'is_success' => [
                'type' => 'BOOLEAN',
                'null' => false
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('verification_token');
        $this->forge->addKey('created_at');

        $this->forge->createTable('verification_logs');
    }

    public function down()
    {
        //
    }
}
