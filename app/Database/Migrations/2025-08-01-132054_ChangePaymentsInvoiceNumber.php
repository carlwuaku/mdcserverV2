<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangePaymentsInvoiceNumber extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('payments', 'payments_invoice_number_foreign');

        $this->forge->modifyColumn('payments', [
            'invoice_number' => [
                'name' => 'invoice_uuid',
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
