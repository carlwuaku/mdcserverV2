<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeInvoiceLinePurposeCode extends Migration
{
    public function up()
    {

        $this->forge->modifyColumn('invoice_line_items', [
            'purpose_code' => [
                'name' => 'service_code',
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ]
        ]);




    }

    public function down()
    {
        //
    }
}
