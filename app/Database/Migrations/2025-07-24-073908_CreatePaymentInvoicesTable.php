<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentInvoicesTable extends Migration
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
            'unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
            'phone_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
            'application_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
            'post_url' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'redirect_url' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'purpose' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'year' => [
                'type' => 'YEAR',
                'null' => true,
                'default' => NULL
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'default' => 'GHS',
                'null' => false,
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Pending', 'Paid', 'Overdue', 'Cancelled', 'Payment Approved'],
                'default' => 'Pending',
                'null' => false,
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('invoice_number');
        $this->forge->addKey('due_date');
        $this->forge->addKey('name', false);
        $this->forge->addKey('phone_number', false);
        $this->forge->addKey('email', false);
        $this->forge->addKey('unique_id', false, true);
        $this->forge->addKey('purpose', false);
        $this->forge->addKey('year', false);
        $this->forge->addKey('status', false);
        $this->forge->createTable('invoices', true);

        // Add UUID trigger for invoice_id
        $trigger = "
       CREATE TRIGGER before_insert_invoices
       BEFORE INSERT ON invoices
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        $this->forge->dropTable('invoices');
    }
}
