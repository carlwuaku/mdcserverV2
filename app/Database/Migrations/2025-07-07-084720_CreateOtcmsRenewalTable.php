<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOtcmsRenewalTable extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'renewal_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'license_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
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

            'weekdays_start_time' => [
                'type' => 'TIME',
                'null' => true,
                'default' => null
            ],
            'weekdays_end_time' => [
                'type' => 'TIME',
                'null' => true,
                'default' => null
            ],
            'weekend_start_time' => [
                'type' => 'TIME',
                'null' => true,
                'default' => null
            ],
            'weekend_end_time' => [
                'type' => 'TIME',
                'null' => true,
                'default' => null
            ],
            'payment_invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => NULL,
            ]
        ]);

        $this->forge->addKey('renewal_id', false);
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('renewal_id', 'license_renewal', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey('id', true);

        $this->forge->createTable('otcms_renewal', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
    }
}
