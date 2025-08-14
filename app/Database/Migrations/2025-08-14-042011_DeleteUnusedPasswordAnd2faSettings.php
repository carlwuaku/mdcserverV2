<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DeleteUnusedPasswordAnd2faSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->where("key", "forgot_password_email_template")->delete();
        $this->db->table("settings")->where("key", "2fa_email_template")->delete();
    }

    public function down()
    {
        //
    }
}
