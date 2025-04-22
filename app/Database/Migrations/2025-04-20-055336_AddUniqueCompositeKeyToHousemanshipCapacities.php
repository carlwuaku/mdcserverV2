<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueCompositeKeyToHousemanshipCapacities extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('discipline', 'housemanship_disciplines', 'name', 'CASCADE', 'RESTRICT');
        $this->forge->addKey(['facility_name', 'year', 'discipline'], false, true, 'housemanship_capacities_unique');
        $this->forge->processIndexes('housemanship_facility_capacities');
    }

    public function down()
    {
        //
    }
}
