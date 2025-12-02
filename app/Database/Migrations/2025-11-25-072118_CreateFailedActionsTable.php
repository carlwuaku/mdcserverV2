<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFailedActionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true
            ],
            'application_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'UUID of the application if applicable'
            ],
            'action_config' => [
                'type' => 'JSON',
                'null' => false,
                'comment' => 'The ApplicationStageType configuration'
            ],
            'action_data' => [
                'type' => 'JSON',
                'null' => false,
                'comment' => 'The form data that was being processed'
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'The exception message'
            ],
            'error_trace' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Stack trace for debugging'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['failed', 'retrying', 'resolved'],
                'default' => 'failed'
            ],
            'retry_count' => [
                'type' => 'INT',
                'unsigned' => true,
                'default' => 0,
                'comment' => 'Number of retry attempts'
            ],
            'last_retry_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Last retry attempt timestamp'
            ],
            'resolved_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'When the action was successfully resolved'
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'updated_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('application_uuid');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');

        $this->forge->createTable('failed_actions', true);
    }

    public function down()
    {
        $this->forge->dropTable('failed_actions', true);
    }
}
