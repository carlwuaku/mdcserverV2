<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPractitionerPermissions extends Migration
{
    public function up()
    {
        $permissions = [

            ["name" => "Create_Or_Update_Practitioners_Qualifications", "description" => "Allow the user to create or update additional qualifications"],
            ["name" => "View_Practitioner_Qualifications", "description" => "Allow the user to view additional qualifications"],
            ["name" => "Delete_Practitioners_Qualifications", "description" => "Allow the user to delete additional qualifications"],
            ["name" => "Delete_Practitioners_Work_History", "description" => "Allow the user to delete"],
            ["name" => "View_Practitioners_Work_History", "description" => "Allow the user to view work history"],
            ["name" => "Create_Or_Update_Practitioners_Work_History", "description" => "Allow the user to create or update work history"],

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
        //
    }
}
