<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDescriptionToInvoices extends Migration
{
    public function up()
    {
        $this->forge->addColumn('invoices', [
            'description' => [
                'type' => 'TEXT',
                'null' => false
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('invoices', 'description');
    }
}
