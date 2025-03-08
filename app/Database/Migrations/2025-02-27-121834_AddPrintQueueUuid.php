<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintQueueUuid extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_print_queues_uuid
       BEFORE INSERT ON print_queues
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
