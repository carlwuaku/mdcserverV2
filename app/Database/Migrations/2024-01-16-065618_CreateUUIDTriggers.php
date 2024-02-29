<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUUIDTriggers extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        // Execute raw SQL statement
       $trigger_practitioners = "
       CREATE TRIGGER before_insert_practitioners
       BEFORE INSERT ON practitioners
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";

       
       $this->db->query($trigger_practitioners);
    }

    public function down()
    {
        //
    }
}
