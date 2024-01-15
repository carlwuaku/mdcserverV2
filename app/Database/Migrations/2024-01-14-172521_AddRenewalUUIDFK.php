<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalUUIDFK extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE practitioner_renewal ADD CONSTRAINT fk_practitioner_uuid FOREIGN KEY (practitioner_uuid) REFERENCES practitioners(uuid)");

    }

    public function down()
    {
        //
    }
}
