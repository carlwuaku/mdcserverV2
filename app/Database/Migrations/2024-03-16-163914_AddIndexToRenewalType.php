<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndexToRenewalType extends Migration
{
    public function up()
    {
        $this->forge->addKey('practitioner_type');
        $this->forge->processIndexes('practitioner_renewal');
    }

    public function down()
    {
        //
    }
}
