<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdAttendanceFKs extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('cpd_id', 'cpd_topics', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('cpd_attendance');
    }

    public function down()
    {
        //
    }
}
