<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentsInvoiceUuidFK extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('invoice_uuid', 'invoices', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('payments');
    }

    public function down()
    {
        //
    }
}
