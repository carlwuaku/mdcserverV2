<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintRenewalPermissions extends Migration
{
    public function up()
    {
        $data = [
            [
                'name' => 'Print_Renewal_Certificates_practitioners',
                'description' => 'Permission to print renewal certificates for practitioners',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Print_Renewal_Certificates_facilities',
                'description' => 'Permission to print renewal certificates for facilities',
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
                'Print_Renewal_Certificates_practitioners',
                'Print_Renewal_Certificates_facilities'
            ])
            ->delete();
    }
}
