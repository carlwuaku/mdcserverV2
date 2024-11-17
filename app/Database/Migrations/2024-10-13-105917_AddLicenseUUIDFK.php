<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseUUIDFK extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('license_uuid', 'licenses', 'uuid', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('license_renewal');
    }

    public function down()
    {
        //
    }
}