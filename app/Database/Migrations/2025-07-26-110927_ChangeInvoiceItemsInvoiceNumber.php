<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeInvoiceItemsInvoiceNumber extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('invoice_line_items', [
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
