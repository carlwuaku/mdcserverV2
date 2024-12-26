<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveUnusedPractitionersTableColumns extends Migration
{
    public function up()
    {


        //drop the foreign key constraint for uuid
        $this->forge->dropTable('practitioner_renewal', true);
        $fields = ["uuid", "registration_date", "status", "email", "postal_address", "picture", "phone", "region", "district", "portal_access", "deleted_at", "updated_at", "created_on", "modified_on", "deleted_by", "modified_by", "created_by", "deleted", "created_at"];
        foreach ($fields as $field) {
            if ($this->db->fieldExists($field, 'practitioners')) {
                $this->forge->dropColumn('practitioners', $field);
            }
        }


    }

    public function down()
    {
        //
    }
}
