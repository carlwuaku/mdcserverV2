<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueCompositeKeyToHousemanshipAvailability extends Migration
{
    public function up()
    {
        $this->forge->addKey(['facility_name', 'year', 'category'], false, true, 'housemanship_availabily_unique');
        $this->forge->processIndexes('housemanship_facility_availability');
    }

    public function down()
    {
        //
    }
}
