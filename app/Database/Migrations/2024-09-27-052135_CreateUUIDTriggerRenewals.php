<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUUIDTriggerRenewals extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_license_renewal
       BEFORE INSERT ON license_renewal
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
