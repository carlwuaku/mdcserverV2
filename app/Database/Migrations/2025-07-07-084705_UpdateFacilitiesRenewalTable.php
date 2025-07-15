<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateFacilitiesRenewalTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('facility_renewal', [
            'selection_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'authorization_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'print_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'actual_print_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'authorized_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => NULL,
            ],
            'printed_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => NULL,
            ],
            'receive_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'received_by' => [
                'type' => 'INT',
                'null' => true,
                'default' => NULL,
            ],
            'payment_file' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'default' => NULL,
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => NULL,
            ],
            'payment_file_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'support_staff' => [
                'type' => 'JSON',
                'null' => true,
                'default' => NULL,
            ],
            'practitioner_in_charge_details' => [
                'type' => 'JSON',
                'null' => true,
                'default' => NULL,
            ],
            'payment_invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => NULL,
            ]
        ]);
        $this->forge->addKey('selection_date', false);
        $this->forge->addKey('authorization_date', false);
        $this->forge->addKey('print_date', false);
        $this->forge->addKey('region', false);
        $this->forge->addKey('payment_invoice_number', false);
        $this->forge->processIndexes('facility_renewal');


    }

    public function down()
    {
        //
    }
}
