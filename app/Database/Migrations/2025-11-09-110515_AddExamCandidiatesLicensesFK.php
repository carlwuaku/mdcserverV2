<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExamCandidiatesLicensesFK extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('intern_code', 'licenses', 'license_number', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('exam_candidates');
    }

    public function down()
    {
        //
    }
}
