<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBusinessTypeToFacilityRenewal extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('business_type', 'license_renewal')) {
            $this->forge->dropColumn('license_renewal', 'business_type');
        }
        if (!$this->db->fieldExists("business_type", 'facility_renewal')) {
            $this->forge->addColumn('facility_renewal', [
                "business_type" => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
