<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangHousemanshipPostingDetailsNameToNull extends Migration
{
    public function up()
    {
        //set the facility_name field of the housemanship_posting_details table to allow null values
        $fields = [
            'facility_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ],
            'facility_region' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ],
        ];
        $this->forge->modifyColumn('housemanship_postings_details', $fields);
    }

    public function down()
    {
        //
    }
}
