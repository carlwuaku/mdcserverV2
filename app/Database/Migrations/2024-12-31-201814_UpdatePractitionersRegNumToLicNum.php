<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePractitionersRegNumToLicNum extends Migration
{
    public function up()
    {
        try {
            $this->forge->dropForeignKey('practitioners', 'practitioners_registration_number_foreign');

        } catch (\Throwable $th) {
            //do nothing. it may not exist
        }

    }

    public function down()
    {
        //
    }
}
