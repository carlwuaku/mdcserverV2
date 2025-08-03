<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePaymentsMethodNameFK extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('payments', 'payments_method_name_foreign');

    }

    public function down()
    {
        //
    }
}
