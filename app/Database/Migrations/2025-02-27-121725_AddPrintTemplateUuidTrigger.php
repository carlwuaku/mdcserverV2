<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintTemplateUuidTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_print_template_uuid
       BEFORE INSERT ON print_templates
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
