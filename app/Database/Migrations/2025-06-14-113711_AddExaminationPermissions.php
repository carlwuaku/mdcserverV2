<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExaminationPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            ["name" => "Manage_Examination_Candidates", "description" => "Allow the user to add/remove index numbers for examination candidates"],
            ["name" => "Approve_Or_Deny_Examination_Applications", "description" => "Allow the user to approve or deny examination applications"],
            ["name" => "Manage_Examination_Data", "description" => "Allow the user to add/edit/delete examination records"],
            ["name" => "View_Examination_Results", "description" => "Allow the user to view examination results"],
            ["name" => "Create_Or_Update_Examination_Results", "description" => "Allow the user to create or update examination results"]


        ];
        $values = [];
        foreach ($permissions as $permission) {
            $values[] = "('{$permission['name']}', '{$permission['description']}', 'active')";
        }
        //if the Developer role does not exist, create it
        $this->db->query('INSERT IGNORE INTO roles (role_name, description) VALUES ("Developers", "Developers have full access to the system")');
        $this->db->query('INSERT IGNORE INTO permissions (name, description, status) VALUES ' . implode(',', $values));
        //give the Developers role all the permissions
        $this->db->query('INSERT IGNORE INTO role_permissions (role, permission) SELECT "Developers", name FROM permissions');
    }

    public function down()
    {
        // This migration does not need a down method as it is not reversible.
        // If you need to remove the permissions, you can do so manually or create a new migration for that purpose.
    }
}
