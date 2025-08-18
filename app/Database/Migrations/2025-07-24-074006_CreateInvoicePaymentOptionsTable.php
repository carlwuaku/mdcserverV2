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
            'invoice_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
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

        $this->forge->addForeignKey('invoice_uuid', 'invoices', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('method_name', 'payment_methods', 'method_name', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('invoice_payment_options', true);
    }

    public function down()
    {
        $this->forge->dropTable('invoice_payment_options');
    }

}
