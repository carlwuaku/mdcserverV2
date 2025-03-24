<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePractionersRenewalLicenseFKs extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('practitioners_renewal', 'practitioners_renewal_license_number_foreign');
        $this->forge->processIndexes('practitioners_renewal');
    }

    public function down()
    {
        //
    }
}
