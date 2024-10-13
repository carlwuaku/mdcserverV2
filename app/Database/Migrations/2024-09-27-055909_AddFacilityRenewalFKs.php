<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFacilityRenewalFKs extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('renewal_id', 'license_renewal', 'id', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('facility_renewal');
    }

    public function down()
    {
        //
    }
}
