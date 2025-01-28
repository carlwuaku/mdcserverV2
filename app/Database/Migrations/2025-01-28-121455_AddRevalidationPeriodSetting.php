<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRevalidationPeriodSetting extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "revalidation_period",
            "value" => "0",
            "type" => "string",
            "control_type" => "text",
        ]);
    }

    public function down()
    {
        //
    }
}
