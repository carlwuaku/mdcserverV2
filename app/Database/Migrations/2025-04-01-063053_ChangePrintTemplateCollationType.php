<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangePrintTemplateCollationType extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `print_templates` CHANGE `template_name` `template_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
    }

    public function down()
    {
        //
    }
}
