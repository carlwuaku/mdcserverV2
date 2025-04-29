<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateHousemanshipPostingFK extends Migration
{
    public function up()
    {
        try {
            $this->forge->dropForeignKey('housemanship_postings', 'housemanship_postings_facility_id_foreign');
            $this->forge->processIndexes('housemanship_postings');
        } catch (\Throwable $th) {
            log_message('error', 'Error dropping foreign key: ' . $th->getMessage());
        }


    }

    public function down()
    {
        //
    }
}
