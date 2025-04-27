<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCollegeMembershipSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "Practitioners",
            "key" => "college_membership_types",
            "value" => "Member;Fellow",
            "type" => "string",
            "control_type" => "list",
        ]);
    }

    public function down()
    {
        //
    }
}
