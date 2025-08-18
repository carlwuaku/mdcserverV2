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
        $this->forge->createTable('payment_audit_log', true);

        // Add UUID trigger for log_id
        $trigger = "
       CREATE TRIGGER before_insert_payment_audit_log
       BEFORE INSERT ON payment_audit_log
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        $this->forge->dropTable('payment_audit_log');
    }
}
