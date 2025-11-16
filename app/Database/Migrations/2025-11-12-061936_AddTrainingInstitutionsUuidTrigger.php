<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTrainingInstitutionsUuidTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_training_institutions
       BEFORE INSERT ON training_institutions
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
