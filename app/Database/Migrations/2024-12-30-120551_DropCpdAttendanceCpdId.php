<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropCpdAttendanceCpdId extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('cpd_attendance', 'cpd_id');
    }

    public function down()
    {
        //
    }
}
