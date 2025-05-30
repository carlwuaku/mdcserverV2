<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndexesToPractitionerRenewalFields extends Migration
{
    public function up()
    {
        $fields = ["category", "sex", "practitioner_type", "register_type"];
        foreach ($fields as $field) {
            $this->forge->addKey($field, false, false, "practitioners_renewal_" . "$field");
        }

        $this->forge->processIndexes('practitioners_renewal');
    }

    public function down()
    {
        //
    }
}
