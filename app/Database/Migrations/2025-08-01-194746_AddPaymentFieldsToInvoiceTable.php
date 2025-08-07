<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentFieldsToInvoiceTable extends Migration
{
    public function up()
    {

        $this->forge->addColumn('invoices', [
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ],
            'origin' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'payment_file' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
                'comment' => 'Payment file path if verification of payment was done by uploading evidence of payment'
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'payment_file_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'online_payment_status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ],
            'online_payment_response' => [
                'type' => 'JSON',
                'null' => true,
                'default' => null
            ],
            'mda_branch_code' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ]
        ]);

        $this->forge->addKey('payment_method', false, false, 'invoices_payment_method');
        $this->forge->addKey('payment_date', false, false, 'invoices_payment_date');
        $this->forge->addKey('online_payment_status', false, false, 'invoices_online_payment_status');
        $this->forge->addKey('mda_branch_code', false, false, 'invoices_mda_branch_code');
        $this->forge->processIndexes('invoices');
    }

    public function down()
    {
        $this->forge->dropKey('invoices', 'invoices_payment_method');
        $this->forge->dropKey('invoices', 'invoices_payment_date');
        $this->forge->dropKey('invoices', 'invoices_online_payment_status');
        $this->forge->dropKey('invoices', 'invoices_mda_branch_code');
        $this->forge->dropColumn('invoices', 'payment_method');
        $this->forge->dropColumn('invoices', 'origin');
        $this->forge->dropColumn('invoices', 'payment_file');
        $this->forge->dropColumn('invoices', 'payment_date');
        $this->forge->dropColumn('invoices', 'payment_file_date');
        $this->forge->dropColumn('invoices', 'online_payment_status');
        $this->forge->dropColumn('invoices', 'online_payment_response');
        $this->forge->dropColumn('invoices', 'mda_branch_code');
    }
}
