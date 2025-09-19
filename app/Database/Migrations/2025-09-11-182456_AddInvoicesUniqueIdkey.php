<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoicesUniqueIdkey extends Migration
{
    public function up()
    {
        $this->forge->addKey('unique_id', false, false, 'invoices_unique_id');
        $this->forge->processIndexes('invoices');
    }

    public function down()
    {
        //
    }
}
