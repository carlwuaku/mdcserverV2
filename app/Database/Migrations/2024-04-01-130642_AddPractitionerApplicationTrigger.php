<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPractitionerApplicationTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_application
       BEFORE INSERT ON application_forms
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
