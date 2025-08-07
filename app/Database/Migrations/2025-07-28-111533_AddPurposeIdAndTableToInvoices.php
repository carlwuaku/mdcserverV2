<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPurposeIdAndTableToInvoices extends Migration
{
    public function up()
    {
        //add currency to fees table
        $this->forge->addColumn('invoices', [
            'purpose_table' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'the table name for the activity for which this invoice was generated. e.g. license_renewal, application_forms'
            ],
            'purpose_table_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'default' => null,
                'comment' => 'the UUID of the row in the table for which this invoice was generated. it has to be a UUID'
            ]
        ]);
        $this->forge->addKey('purpose_table', false, false, 'invoices_purpose_table');
        $this->forge->addKey('purpose_table_uuid', false, false, 'invoices_purpose_table_uuid');
        $this->forge->processIndexes('invoices');

    }

    public function down()
    {
        //
    }
}
