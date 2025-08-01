<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveBusinessTypeFromRenewal extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('business_type', 'license_renewal')) {
            $this->forge->dropColumn('license_renewal', 'business_type');

        }
    }

    public function down()
    {
        //
    }
}
