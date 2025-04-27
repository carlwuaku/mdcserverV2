<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseDetailsToHousemanshipPosting extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('practitioner_details', 'housemanship_postings')) {
            $this->forge->addColumn('housemanship_postings', [
                'practitioner_details' => [
                    'type' => 'JSON',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
