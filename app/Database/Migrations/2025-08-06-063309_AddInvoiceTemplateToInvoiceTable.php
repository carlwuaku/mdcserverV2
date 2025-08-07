<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceTemplateToInvoiceTable extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('invoice_template', 'invoices')) {
            $this->forge->addColumn('invoices', [
                'invoice_template' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'default' => null
                ]
            ]);
        }

        $this->forge->addForeignKey('invoice_template', 'print_templates', 'template_name', 'CASCADE', 'RESTRICT');
    }

    public function down()
    {
        //
    }
}
