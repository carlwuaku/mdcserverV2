<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintTemplatePermissions extends Migration
{
    public function up()
    {
        $data = [
            [
                'permission' => 'Create_Print_Templates',
                'description' => 'Permission to create new print templates',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'permission' => 'Delete_Print_Templates',
                'description' => 'Permission to delete existing print templates',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'permission' => 'Edit_Print_Templates',
                'description' => 'Permission to edit existing print templates',
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
                'Create_Print_Templates',
                'Delete_Print_Templates',
                'Edit_Print_Templates'
            ])
            ->delete();
    }
} 