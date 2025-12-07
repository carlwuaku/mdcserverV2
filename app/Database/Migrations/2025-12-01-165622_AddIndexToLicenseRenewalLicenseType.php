<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndexToLicenseRenewalLicenseType extends Migration
{
    public function up()
    {
        try {
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
        } catch (\Throwable $th) {
            log_message('error', 'Error adding foreign keys to license_renewal table: ' . $th);
        }

    }

    public function down()
    {
        //
    }
}
