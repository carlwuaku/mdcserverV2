<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRegisterTypeFromPractitioners extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists("register_type", 'practitioners')) {
            $this->forge->dropColumn('practitioners', 'register_type');
        }
    }

    public function down()
    {
        //
    }
}
