<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIndexToOtcmsRenewalId extends Migration
{
    public function up()
    {

        $this->forge->addKey("renewal_id", false, true, 'otcms_renewal_id_unique');
        $this->forge->processIndexes('otcms_renewal');
    }

    public function down()
    {
        //
    }
}
