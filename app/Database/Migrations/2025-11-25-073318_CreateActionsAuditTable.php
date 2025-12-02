<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActionsAuditTable extends Migration
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
                'comment' => 'The form data that was processed'
            ],
            'action_result' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'The result of the action execution'
            ],
            'action_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Type of action (email, payment, api_call, etc.)'
            ],
            'execution_time_ms' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'comment' => 'Execution time in milliseconds'
            ],
            'triggered_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'User ID who triggered the action'
            ],
            'created_at' => [
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
        $this->forge->addKey('action_type');
        $this->forge->addKey('created_at');
        $this->forge->addKey('triggered_by');

        $this->forge->createTable('actions_audit', true);
    }

    public function down()
    {
        $this->forge->dropTable('actions_audit', true);
    }
}
