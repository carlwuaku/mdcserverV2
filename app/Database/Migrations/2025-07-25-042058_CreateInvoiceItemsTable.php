<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoiceItemsTable extends Migration
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
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'purpose_code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'quantity' => [
                'type' => 'DECIMAL',
                'constraint' => '8,2',
                'default' => 1.00,
                'null' => false,
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'line_total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);

        $this->forge->addKey('uuid', false, true);
        $this->forge->addKey('invoice_number');
        $this->forge->addForeignKey('invoice_number', 'invoices', 'invoice_number', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('purpose_code', 'payment_purposes', 'purpose_code', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('invoice_line_items', true);

        // Add UUID trigger for line_item_id
        $trigger = "
       CREATE TRIGGER before_insert_invoice_line_items
       BEFORE INSERT ON invoice_line_items
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        $this->forge->dropTable('invoice_line_items');
    }
}
