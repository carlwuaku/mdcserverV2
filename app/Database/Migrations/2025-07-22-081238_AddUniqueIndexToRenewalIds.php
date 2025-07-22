<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIndexToRenewalIds extends Migration
{
    public function up()
    {
        $this->forge->addKey("renewal_id", false, true);
        $this->forge->processIndexes('practitioners_renewal');


    }

    public function down()
    {
        //
    }
}
