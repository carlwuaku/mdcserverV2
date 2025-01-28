<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePractitionerTrigger extends Migration
{
    public function up()
    {
        $this->db->query("DROP TRIGGER IF EXISTS before_insert_practitioners");
    }

    public function down()
    {
        //
    }
}
