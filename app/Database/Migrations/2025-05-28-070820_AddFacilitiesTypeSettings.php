<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFacilitiesTypeSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "Facilities",
            "key" => "business_types",
            "value" => "Manufacturing;Manufacturing Wholesale;Retail;Wholesale;Wholesale/Retail;Hospital;Clinic;Other",
            "type" => "string",
            "control_type" => "list",
        ]);
    }

    public function down()
    {
        //
    }
}
