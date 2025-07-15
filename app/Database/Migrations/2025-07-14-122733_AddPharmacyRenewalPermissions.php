<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPharmacyRenewalPermissions extends Migration
{
    public function up()
    {
        $data = [

            [
                'name' => 'Mark_Facilities_Renewal_Pending_Authorization',
                'description' => 'Permission to update the status of facilities to renewal pending authorization by R&L',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mark_Otcms_Renewal_Pending_Authorization',
                'description' => 'Permission to update the status of OTCMS to pending authorization by R&L',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mark_Otcms_Renewal_Approved',
                'description' => 'Permission to update the status of OTCMS  renewal to approved',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Insert the permissions
        $this->db->table('permissions')->insertBatch($data);
    }

    public function down()
    {
        //
    }
}
