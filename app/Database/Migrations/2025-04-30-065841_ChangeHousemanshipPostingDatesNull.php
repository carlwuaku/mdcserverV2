<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeHousemanshipPostingDatesNull extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('housemanship_postings_details')) {
            // Check if the name field exists
            if ($this->db->fieldExists('start_date', 'housemanship_postings_details')) {
                // Modify the name field to be NOT NULL
                $this->forge->modifyColumn('housemanship_postings_details', [
                    'start_date' => [
                        'type' => 'DATE',
                        'null' => true,
                        'default' => null
                    ]
                ]);
            }
            if ($this->db->fieldExists('end_date', 'housemanship_postings_details')) {
                // Modify the name field to be NOT NULL
                $this->forge->modifyColumn('housemanship_postings_details', [

                    'end_date' => [
                        'type' => 'DATE',
                        'null' => true,
                        'default' => null
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        //
    }
}
