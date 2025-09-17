<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveInvoicesUniqueIdUniqueId extends Migration
{
    public function up()
    {
        $this->forge->dropKey('invoices', 'unique_id');
        $this->forge->processIndexes('invoices');
    }

    public function down()
    {
        //
    }
}
