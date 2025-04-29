<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropHousemanshipPostingsUnusedFields extends Migration
{
    public function up()
    {
        //facility_id	start_date	end_date
        if ($this->db->fieldExists('facility_id', 'housemanship_postings')) {
            $this->forge->dropColumn('housemanship_postings', 'facility_id');
        }
        if ($this->db->fieldExists('start_date', 'housemanship_postings')) {
            $this->forge->dropColumn('housemanship_postings', 'start_date');
        }
        if ($this->db->fieldExists('end_date', 'housemanship_postings')) {
            $this->forge->dropColumn('housemanship_postings', 'end_date');
        }
    }

    public function down()
    {
        //
    }
}
