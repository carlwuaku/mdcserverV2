<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFacilityRenewal extends Migration
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

            'practitioner_in_charge' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default' => null
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
            'in_charge_start_time' => [
                'type' => 'TIME',
                'null' => true,
                'default' => null
            ],
            'in_charge_end_time' => [
                'type' => 'TIME',
                'null' => true,
                'default' => null
            ]
        ]);
        $this->forge->addKey('license_number', false);

        $this->forge->addKey('renewal_id', false);
        $this->forge->addKey('practitioner_in_charge', false);
        $this->forge->createTable('facility_renewal', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
    }
}
