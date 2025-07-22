<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSupportStaffToFacilityRenewal extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists("support_staff", 'facility_renewal')) {
            $this->forge->addColumn('facility_renewal', [
                "support_staff" => [
                    'type' => 'JSON',
                    'null' => true,
                    'default' => null
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
