<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertPasswordResetTimeoutIntoSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "password_reset_token_timeout",
            "value" => '15',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
    }

    public function down()
    {
        //
    }
}
