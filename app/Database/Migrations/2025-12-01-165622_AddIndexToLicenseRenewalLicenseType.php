<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndexToLicenseRenewalLicenseType extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('license_renewal', [
            'license_type' => [
                'type' => 'varchar',
                'constraint' => 255
            ]
        ]);
        $this->forge->addKey('license_type');
        $this->forge->addKey('license_number');
        $this->forge->addKey('approve_online_certificate');
        $this->forge->addKey('license_uuid');
        $this->forge->addKey('in_print_queue');
        $this->forge->addKey('print_template');
        $this->forge->addKey('online_print_template');
        $this->forge->addKey('in_print_queue');


        $this->forge->processIndexes('license_renewal');
    }

    public function down()
    {
        //
    }
}
