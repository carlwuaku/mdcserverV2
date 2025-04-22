<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateHousemanshipPostingCreatedOn extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('housemanship_postings')) {
            // Check if the name field exists
            if ($this->db->fieldExists('created_on', 'housemanship_postings')) {
                // Modify the name field to be NOT NULL
                $this->forge->modifyColumn('housemanship_postings', [
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                    ]
                ]);
            }
        }
    }

    public function down()
    {
        //
    }
}
