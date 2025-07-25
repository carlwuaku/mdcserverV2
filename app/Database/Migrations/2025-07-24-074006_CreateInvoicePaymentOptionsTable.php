<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoicePaymentOptionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 20,
                'AUTO_INCREMENT' => true
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
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');

        $this->forge->addUniqueKey(['invoice_number', 'method_name']);
        $this->forge->addForeignKey('invoice_number', 'invoices', 'invoice_number', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('method_name', 'payment_methods', 'method_name', 'CASCADE', 'CASCADE');
        $this->forge->createTable('invoice_payment_options');
    }

    public function down()
    {
        $this->forge->dropTable('invoice_payment_options');
    }

}
