<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentVerification extends Migration
{
    public function up()
    {
        $this->forge->addField('id');

        //         CREATE TABLE documents (
//     id INT PRIMARY KEY AUTO_INCREMENT,
//     document_id VARCHAR(32) UNIQUE NOT NULL,
//     verification_token VARCHAR(64) UNIQUE NOT NULL,
//     document_type VARCHAR(50) NOT NULL,
//     content_hash VARCHAR(64) NOT NULL,
//     digital_signature TEXT NOT NULL,
//     issuing_department VARCHAR(100) NOT NULL,
//     created_at DATETIME NOT NULL,
//     expires_at DATETIME NOT NULL,
//     is_revoked BOOLEAN DEFAULT FALSE,
//     INDEX (verification_token)
// );

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
                'null' => false,
            ],
            'document_id' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'unique' => true,
                'null' => false
            ],
            'verification_token' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'unique' => true,
                'null' => false
            ],
            'document_type' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false
            ],
            'content_hash' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false
            ],
            'digital_signature' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'issuing_department' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'is_revoked' => [
                'type' => 'BOOLEAN',
                'default' => false
            ]
        ]);

        $this->forge->addKey('verification_token');

        $this->forge->createTable('documents', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
    }
}
