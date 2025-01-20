<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePractitionerUnusedColumns extends Migration
{
    public function up()
    {
        $fields = ['portal_access_message', 'last_renewal_start', 'last_renewal_expiry', 'last_renewal_status'];
        foreach ($fields as $field) {
            if (!$this->db->fieldExists($field, 'practitioners')) {
                continue;
            }
            $this->forge->dropColumn('practitioners', $field);
        }
    }

    public function down()
    {
        //
    }
}
