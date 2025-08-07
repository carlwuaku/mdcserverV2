<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveInvoicePaymentOptionFK extends Migration
{
    public function up()
    {

        $this->forge->dropForeignKey('invoice_payment_options', 'invoice_payment_options_method_name_foreign');


    }

    public function down()
    {
        //
    }
}
