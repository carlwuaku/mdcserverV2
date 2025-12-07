<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiIntegrationPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            // Institution permissions
            ['name' => 'View_Institutions', 'description' => 'View institutions for API integration'],
            ['name' => 'Create_Institutions', 'description' => 'Create new institutions for API integration'],
            ['name' => 'Edit_Institutions', 'description' => 'Edit institutions'],
            ['name' => 'Delete_Institutions', 'description' => 'Delete institutions'],

            // API Key permissions
            ['name' => 'View_API_Keys', 'description' => 'View API keys'],
            ['name' => 'Create_API_Keys', 'description' => 'Generate new API keys'],
            ['name' => 'Edit_API_Keys', 'description' => 'Edit API key settings'],
            ['name' => 'Delete_API_Keys', 'description' => 'Delete API keys'],
            ['name' => 'Revoke_API_Keys', 'description' => 'Revoke API keys'],
        ];

        foreach ($permissions as $permission) {
            // Check if permission already exists
            $existing = $this->db->table('permissions')
                ->where('name', $permission['name'])
                ->get()
                ->getRow();

            if (!$existing) {
                $permission['status'] = 'active';
                $this->db->table('permissions')->insert($permission);
            }
        }
    }

    public function down()
    {
        $permissionNames = [
            'View_Institutions',
            'Create_Institutions',
            'Edit_Institutions',
            'Delete_Institutions',
            'View_API_Keys',
            'Create_API_Keys',
            'Edit_API_Keys',
            'Delete_API_Keys',
            'Revoke_API_Keys',
        ];

        $this->db->table('permissions')
            ->whereIn('name', $permissionNames)
            ->delete();
    }
}
