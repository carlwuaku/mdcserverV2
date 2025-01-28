<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPractitionerLicenseNumberFK extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('registration_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('practitioners');
    }

    public function down()
    {
        //
    }
}
