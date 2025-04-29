<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToHousemanshipApplications extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('status', 'housemanship_postings_applications')) {
            $this->forge->addColumn('housemanship_postings_applications', [
                'status' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'constraint' => 100,
                    'default' => 'Pending',
                ],
            ]);


        }
    }

    public function down()
    {
        //
    }
}
