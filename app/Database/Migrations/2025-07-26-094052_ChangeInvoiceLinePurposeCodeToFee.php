<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeInvoiceLinePurposeCodeToFee extends Migration
{
    public function up()
    {
        //make the fees->name and fees->service_code unique
        try {
            $this->forge->addKey('name', false, true, 'fees_name_unique');
            $this->forge->addKey('service_code', false, true);
            $this->forge->processIndexes('fees');
        } catch (\Throwable $th) {
            log_message('error', 'Error adding foreign keys to fees table: ' . $th);
        }

        //rename the invoice_line_items->purpose_code column to service_code
        try {
            $this->forge->dropForeignKey('invoice_line_items', 'invoice_line_items_purpose_code_foreign');

        } catch (\Throwable $th) {
            log_message('error', 'Error dropping foreign keys from invoice_line_items table: ' . $th);
        }
        //drop the invoice_line_items->purpose_code index
        try {
            $this->db->query("ALTER TABLE `invoice_line_items` DROP INDEX `invoice_line_items_purpose_code_foreign`;");
        } catch (\Throwable $th) {
            log_message('error', 'Error DROP INDEX `invoice_line_items_purpose_code_foreign`; ' . $th);
        }

    }

    public function down()
    {
        //
    }
}
