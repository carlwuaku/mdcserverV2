<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveLastRenewalColsFromPractitioner extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('last_renewal_start', 'practitioners')) {
            $this->forge->dropColumn('practitioners', 'last_renewal_start');
        }
        if ($this->db->fieldExists('last_renewal_expiry', 'practitioners')) {
            $this->forge->dropColumn('practitioners', 'last_renewal_expiry');
        }
        if ($this->db->fieldExists('last_renewal_status', 'practitioners')) {
            $this->forge->dropColumn('practitioners', 'last_renewal_status');
        }

    }

    public function down()
    {
        //
    }
}
