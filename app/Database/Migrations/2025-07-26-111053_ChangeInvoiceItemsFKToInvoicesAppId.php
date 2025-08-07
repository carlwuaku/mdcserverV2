<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeInvoiceItemsFKToInvoicesAppId extends Migration
{
    public function up()
    {
        try {
            $this->forge->dropForeignKey('invoice_line_items', 'invoice_line_items_invoice_number_foreign');

        } catch (\Throwable $th) {
            log_message('error', 'Error dropping foreign keys from invoice_line_items table: ' . $th);
        }

        $this->forge->addForeignKey('invoice_uuid', 'invoices', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('invoice_line_items');

    }

    public function down()
    {
        //
    }
}
