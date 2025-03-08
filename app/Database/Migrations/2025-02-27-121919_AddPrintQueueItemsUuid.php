<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintQueueItemsUuid extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_print_queue_items_uuid
       BEFORE INSERT ON print_queue_items
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
