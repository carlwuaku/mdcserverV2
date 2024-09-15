<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUUIDTriggerLicenses extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_license
       BEFORE INSERT ON licenses
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        //
    }
}
