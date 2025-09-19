<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoicesSelectedPaymentMethod extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('selected_payment_method', 'invoices')) {
            $this->forge->addColumn('invoices', [
                'selected_payment_method' => [
                    'type' => 'ENUM',
                    'constraint' => ["Ghana.gov Platform", "In-Person"],
                    'null' => true,
                    'default' => null
                ]
            ]);
        }

        $this->forge->addKey('selected_payment_method');
        $this->forge->processIndexes('invoices');
    }

    public function down()
    {
        //
    }
}
