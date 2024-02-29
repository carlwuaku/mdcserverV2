<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInstitutionTypesSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "Doctors",
            "key"=> "work_institution_types",
            "value"=> "CHAG;Government;Private",
            "type"=> "string",
            "control_type"=> "list",
        ]);
    }

    public function down()
    {
        //
    }
}
