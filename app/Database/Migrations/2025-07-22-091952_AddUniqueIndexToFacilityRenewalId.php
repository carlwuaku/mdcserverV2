<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIndexToFacilityRenewalId extends Migration
{
    public function up()
    {
        $this->forge->addKey("renewal_id", false, true, 'facility_renewal_id_unique');
        $this->forge->processIndexes('facility_renewal');

    }

    public function down()
    {
        //
    }
}
