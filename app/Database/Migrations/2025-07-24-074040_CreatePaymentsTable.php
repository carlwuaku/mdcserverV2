<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentsTable extends Migration
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
            'invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'method_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'default' => 'GHS',
                'null' => false,
            ],
            'payment_date' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'completed', 'failed', 'refunded'],
                'default' => 'pending',
                'null' => false,
            ],
            'reference_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid', false, true);
        $this->forge->addKey('invoice_number');
        $this->forge->addKey('reference_number');
        $this->forge->addKey('status');
        $this->forge->addKey('payment_date');
        $this->forge->addForeignKey('invoice_number', 'invoices', 'invoice_number', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('method_name', 'payment_methods', 'method_name', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('payments', true);

        // Add UUID trigger for payment_id
        $trigger = "
       CREATE TRIGGER before_insert_payments
       BEFORE INSERT ON payments
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        $this->forge->dropTable('payments');
    }
}
