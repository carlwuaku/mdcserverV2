<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveUnusedPaymentTables extends Migration
{
    public function up()
    {
        $this->forge->dropTable('payment_purposes', true);
        $this->forge->dropTable('payment_methods', true);
        try {
            $this->forge->dropForeignKey('payment_files', 'payment_files_payment_uuid_foreign');

        } catch (\Throwable $th) {
            log_message('error', 'Error dropping foreign keys from payment_files table: ' . $th);
        }
        $this->forge->dropTable('payment_files', true);
        $this->forge->dropForeignKey('payments', 'payments_invoice_uuid_foreign');


        $this->forge->dropTable('payments', true);


        $this->forge->dropTable('online_payments', true);
        $this->forge->dropForeignKey('online_payment_details', 'online_payment_details_payment_uuid_foreign');


        $this->forge->dropTable('online_payment_details', true);
        $this->forge->dropForeignKey('offline_payment_details', 'offline_payment_details_payment_uuid_foreign');


        $this->forge->dropTable('offline_payment_details', true);
        $this->forge->dropForeignKey('payment_audit_log', 'payment_audit_log_payment_uuid_foreign');


        $this->forge->dropTable('payment_audit_log', true);
    }

    public function down()
    {
        //
    }
}
