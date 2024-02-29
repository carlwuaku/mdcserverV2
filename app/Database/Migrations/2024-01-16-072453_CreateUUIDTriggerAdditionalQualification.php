<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUUIDTriggerAdditionalQualification extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_add_qual
       BEFORE INSERT ON practitioner_additional_qualifications
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
