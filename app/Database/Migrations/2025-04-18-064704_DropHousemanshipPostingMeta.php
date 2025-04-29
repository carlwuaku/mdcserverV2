<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropHousemanshipPostingMeta extends Migration
{
    public function up()
    {
        $this->forge->dropTable('housemanship_postings_metadata', true);
    }

    public function down()
    {
        //
    }
}
