<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveRenewalLicenseFKs extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('license_renewal', 'license_renewal_license_number_foreign');
        $this->forge->dropForeignKey('license_renewal', 'license_renewal_license_uuid_foreign');
        $this->forge->processIndexes('license_renewal');
    }

    public function down()
    {
        //
    }
}
