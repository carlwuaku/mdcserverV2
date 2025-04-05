<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUuidTriggerToUsers extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_users
       BEFORE INSERT ON users
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
