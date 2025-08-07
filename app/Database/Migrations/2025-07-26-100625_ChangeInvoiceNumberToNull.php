<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeInvoiceNumberToNull extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('invoices', [
            'invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'default' => null
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
