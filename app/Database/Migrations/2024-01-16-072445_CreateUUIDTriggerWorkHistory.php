<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUUIDTriggerWorkHistory extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_work_history
       BEFORE INSERT ON practitioner_work_history
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
