<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintHistoryUuid extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_print_history_uuid
       BEFORE INSERT ON print_history
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
