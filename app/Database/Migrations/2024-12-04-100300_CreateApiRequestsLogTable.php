<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiRequestsLogTable extends Migration
{
    protected $tableName = "api_requests_log";

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'api_key_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'comment' => 'Foreign key to api_keys table (null if request failed auth)',
            ],
            'institution_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'comment' => 'Foreign key to institutions table',
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'Unique request identifier for tracking',
            ],
            'method' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'comment' => 'HTTP method (GET, POST, etc.)',
            ],
            'endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'comment' => 'API endpoint path',
            ],
            'query_params' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Query parameters (sanitized)',
            ],
            'request_body_size' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Size of request body in bytes',
            ],
            'response_status' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
                'comment' => 'HTTP response status code',
            ],
            'response_time_ms' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Response time in milliseconds',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'comment' => 'Client IP address',
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Client user agent',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Error message if request failed',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('api_key_id');
        $this->forge->addKey('institution_id');
        $this->forge->addKey('created_at');
        $this->forge->addKey('endpoint');
        $this->forge->addKey('response_status');

        $this->forge->addForeignKey('api_key_id', 'api_keys', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('institution_id', 'institutions', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable($this->tableName, true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable($this->tableName, true);
    }
}
