<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUUIDTriggersAllTables extends Migration
{
    public function up()
    {
        // Execute raw SQL statement
       $trigger = "
       CREATE TRIGGER before_insert_portal
       BEFORE INSERT ON practitioner_portal_edits
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
