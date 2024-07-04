<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HousemanshipSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "Doctors",
            "key"=> "housemanship_facility_types",
            "value"=> "Hospital;Government;Private",
            "type"=> "string",
            "control_type"=> "list",
        ]);

        $this->db->table("settings")->insert([
            "class" => "Doctors",
            "key"=> "housemanship_discipline_types",
            "value"=> "General Medicine;Dentistry;Obstetrics & Gynaecology;Surgery;	
Paediatrics;Internal Medicine;Anaesthesia",
            "type"=> "string",
            "control_type"=> "list",
        ]);

    }

    public function down()
    {
        //
    }
}
