<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalStagePermissions extends Migration
{
    public function up()
    {
        $data = [
            [
                'name' => 'Mark_Facilities_Renewal_Pending_Payment',
                'description' => 'Permission to update the status of facilities to renewal pending payment',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mark_Facilities_Renewal_Pending_Approval',
                'description' => 'Permission to update the status of facilities to renewal pending approval',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mark_Facilities_Renewal_Approved',
                'description' => 'Permission to update the status of facilities to renewal approved',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mark_Practitioners_Renewal_Pending_Payment',
                'description' => 'Permission to update the status of practitioners to renewal pending payment',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mark_Practitioners_Renewal_Pending_Approval',
                'description' => 'Permission to update the status of practitioners to renewal pending approval',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mark_Practitioners_Renewal_Approved',
                'description' => 'Permission to update the status of practitioners to renewal approved',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Insert the permissions
        $this->db->table('permissions')->insertBatch($data);
    }

    public function down()
    {
        // Remove the permissions
        $this->db->table('permissions')
            ->whereIn('permission', [
                'Mark_Facilities_Renewal_Pending_Payment',
                'Mark_Facilities_Renewal_Pending_Approval',
                'Mark_Facilities_Renewal_Approved',
                'Mark_Practitioners_Renewal_Pending_Payment',
                'Mark_Practitioners_Renewal_Pending_Approval',
                'Mark_Practitioners_Renewal_Approved'
            ])
            ->delete();
    }
}
