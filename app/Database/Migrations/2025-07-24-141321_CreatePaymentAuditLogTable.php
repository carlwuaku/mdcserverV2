<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentAuditLogTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 20,
                'AUTO_INCREMENT' => true
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'payment_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'old_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'new_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'changed_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'change_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');

        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('payment_uuid');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('payment_uuid', 'payments', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_audit_log');

        // Add UUID trigger for log_id
        $this->db->query("ALTER TABLE payment_audit_log MODIFY uuid CHAR(36) DEFAULT (UUID())");
    }

    public function down()
    {
        $this->forge->dropTable('payment_audit_log');
    }
}
