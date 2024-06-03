<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApplicationFormTemplateUUIDTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_template
       BEFORE INSERT ON application_form_templates
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
