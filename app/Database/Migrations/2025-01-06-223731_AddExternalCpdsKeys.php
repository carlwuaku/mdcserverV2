<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExternalCpdsKeys extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'CASCADE');
        $this->forge->addKey('attendance_date', false, false, 'external_cpd_attendance_date');
        $this->forge->processIndexes('external_cpd_attendance');
    }

    public function down()
    {
        //
    }
}
