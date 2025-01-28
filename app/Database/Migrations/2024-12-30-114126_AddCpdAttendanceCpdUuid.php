<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdAttendanceCpdUuid extends Migration
{
    public function up()
    {
        //delete the foreign key for the cpd_id
        $this->forge->dropForeignKey('cpd_attendance', 'cpd_attendance_cpd_id_foreign');
        //delete the index for the provider_id
        $this->forge->dropKey('cpd_attendance', 'cpd_attendance_cpd_id_foreign');
    }

    public function down()
    {
        //
    }
}
