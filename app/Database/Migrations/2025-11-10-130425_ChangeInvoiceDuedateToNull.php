<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeInvoiceDuedateToNull extends Migration
{
    public function up()
    {
        $fields = [
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ]
        ];
        $this->forge->modifyColumn('invoices', $fields);
    }

    public function down()
    {
        //
    }
}
