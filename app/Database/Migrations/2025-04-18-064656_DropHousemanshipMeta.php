<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropHousemanshipMeta extends Migration
{
    public function up()
    {
        // Drop the housemanship_meta table if it exists
        $this->forge->dropTable('housemanship_facilities_metadata', true);

    }

    public function down()
    {
        //
    }
}
