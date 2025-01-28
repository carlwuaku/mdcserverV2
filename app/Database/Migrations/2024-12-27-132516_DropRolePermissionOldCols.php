<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRolePermissionOldCols extends Migration
{
    public function up()
    {
        //copy the corresponding data from the permissions and roles tables using the existing permission_id and role_id columns
        $this->db->query('UPDATE role_permissions rp JOIN permissions p ON rp.permission_id = p.permission_id SET rp.permission = p.name');
        $this->db->query('UPDATE role_permissions rp JOIN roles r ON rp.role_id = r.role_id SET rp.role = r.role_name');

        //drop the permission_id and role_id columns and their foreign keys

        $this->forge->dropForeignKey('role_permissions', 'role_permissions_permission_id_foreign');
        $this->forge->dropForeignKey('role_permissions', 'role_permissions_role_id_foreign');

    }

    public function down()
    {
        //
    }
}
