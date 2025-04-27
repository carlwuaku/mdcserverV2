<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHousemanshipApplicationUuidTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_housemanship_applications
       BEFORE INSERT ON housemanship_postings_applications
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
