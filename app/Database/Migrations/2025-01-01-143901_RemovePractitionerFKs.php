<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePractitionerFKs extends Migration
{
    public function up()
    {
        try {
            $this->forge->dropForeignKey('practitioner_additional_qualifications', 'add_qualification_reg_num');

        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $this->forge->dropForeignKey('housemanship_postings', 'housemanship_postings_registration_number_foreign');

        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $this->forge->dropForeignKey('practitioner_work_history', 'work_history_reg_num');

        } catch (\Throwable $th) {
            //throw $th;
        }



    }

    public function down()
    {
        //
    }
}
