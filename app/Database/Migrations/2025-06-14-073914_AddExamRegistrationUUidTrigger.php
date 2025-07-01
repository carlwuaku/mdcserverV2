<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExamRegistrationUUidTrigger extends Migration
{
    public function up()
    {
        $trigger = "
        CREATE TRIGGER before_insert_examination_registrations
        BEFORE INSERT ON examination_registrations
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
