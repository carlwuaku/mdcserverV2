<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveLicnumFKFromCpdattendance extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('cpd_attendance', 'cpd_attendance_license_number_foreign');
        $this->forge->processIndexes('cpd_attendance');
    }

    public function down()
    {
        //
    }
}
