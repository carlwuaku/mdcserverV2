<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExamUuidTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_examinations
       BEFORE INSERT ON examinations
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
