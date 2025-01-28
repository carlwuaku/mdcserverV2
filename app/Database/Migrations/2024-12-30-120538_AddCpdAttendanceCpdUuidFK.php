<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdAttendanceCpdUuidFK extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('cpd_uuid', 'cpd_topics', 'uuid', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('cpd_attendance');
    }

    public function down()
    {
        //
    }
}
