<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalFKs extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('license_renewal');
    }

    public function down()
    {
        //
    }
}
