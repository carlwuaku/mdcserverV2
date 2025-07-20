<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRegionFromAndAddBusinessTypeToFacilityRenewalTable extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('region', 'facility_renewal')) {
            $this->forge->dropColumn('facility_renewal', 'region');
        }
        if (!$this->db->fieldExists("business_type", 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
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
