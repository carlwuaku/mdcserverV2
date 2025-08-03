<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastNameToInvoices extends Migration
{
    public function up()
    {
        $this->forge->addColumn('invoices', [
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ]
        ]);
        $this->forge->modifyColumn('invoices', [
            'name' => [
                'name' => 'first_name',
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ]
        ]);

        $this->forge->addKey('last_name', false, false, 'invoices_last_name');
        $this->forge->processIndexes('invoices');
    }

    public function down()
    {
        $this->forge->dropKey('invoices', 'invoices_last_name');
        $this->forge->dropColumn('invoices', 'last_name');
        $this->forge->modifyColumn('invoices', [
            'name' => [
                'name' => 'first_name',
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ]
        ]);
        $this->forge->processIndexes('invoices');
    }
}
