<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailVerificationTokensTable extends Migration
{
    public function up()
    {
        // Create email_verification_tokens table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'guest_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => '6-digit verification code'
            ],
            'token_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('guest_uuid');
        $this->forge->addKey('email');
        $this->forge->addKey('token');
        $this->forge->addForeignKey('guest_uuid', 'guests', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('email_verification_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('email_verification_tokens', true);
    }
}
