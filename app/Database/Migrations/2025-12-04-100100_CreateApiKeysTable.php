<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeysTable extends Migration
{
    protected $tableName = "api_keys";

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'comment' => 'UUID primary key',
            ],
            'institution_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'comment' => 'Foreign key to institutions table',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Friendly name for the API key',
            ],
            'key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'comment' => 'Public API key identifier (sent in requests)',
            ],
            'key_secret_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Hashed API secret (never store plain text)',
            ],
            'last_4_secret' => [
                'type' => 'VARCHAR',
                'constraint' => 4,
                'comment' => 'Last 4 characters of secret for identification',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'revoked', 'expired'],
                'default' => 'active',
                'comment' => 'API key status',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Optional expiration date',
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Last time this key was used',
            ],
            'last_used_ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'comment' => 'Last IP address that used this key',
            ],
            'rate_limit_per_minute' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 60,
                'comment' => 'Maximum requests per minute',
            ],
            'rate_limit_per_day' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 10000,
                'comment' => 'Maximum requests per day',
            ],
            'scopes' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'JSON array of allowed scopes/permissions',
            ],
            'allowed_endpoints' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'JSON array of allowed endpoint patterns (null = all)',
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Additional metadata (environment, purpose, etc.)',
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'When the key was revoked',
            ],
            'revoked_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User ID who revoked this key',
            ],
            'revocation_reason' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Reason for revocation',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User ID who created this key',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('key_id');
        $this->forge->addKey('institution_id');
        $this->forge->addKey('status');
        $this->forge->addKey('expires_at');
        $this->forge->addKey('deleted_at');

        $this->forge->addForeignKey('institution_id', 'institutions', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable($this->tableName, true, [
            'ENGINE' => 'InnoDB',
        ]);

        // Add trigger to auto-generate UUID
        $this->db->query("
            CREATE TRIGGER before_insert_api_keys
            BEFORE INSERT ON {$this->tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.id IS NULL OR NEW.id = '' THEN
                    SET NEW.id = UUID();
                END IF;
            END;
        ");
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER IF EXISTS before_insert_api_keys");
        $this->forge->dropTable($this->tableName, true);
    }
}
