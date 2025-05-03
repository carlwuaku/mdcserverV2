<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertHousemanshipPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            ["name" => "Create_Or_Update_Housemanship_Facilities", "description" => "Allow the user to create or update the details, capacities, availabilities of housemanship facilities"],
            ["name" => "View_Housemanship_Facilities", "description" => "Allow the user to view the details, capacities, availabilities of housemanship facilities"],
            ["name" => "Delete_Housemanship_Facilities", "description" => "Allow the user to delete housemanship facilities"],
            ["name" => "View_Housemanship_Disciplines", "description" => "Allow the user to view the list of housemanship disciplines. This permission is required for creating updating the capacities of housemanship facilities"],
            ["name" => "Create_Or_Update_Housemanship_Disciplines", "description" => "Allow the user to create or update the list of housemanship disciplines"],
            ["name" => "Delete_Housemanship_Disciplines", "description" => "Allow the user to delete housemanship disciplines"],
            ["name" => "View_Housemanship_Postings", "description" => "Allow the user to view housemanship postings"],
            ["name" => "Delete_Housemanship_Postings", "description" => "Allow the user to delete housemanship postings"],
            ["name" => "Create_Or_Update_Housemanship_Postings", "description" => "Allow the user to create or update housemanship postings"],
            ["name" => "View_Housemanship_Posting_Applications", "description" => "Allow the user to view housemanship posting applications"],
            ["name" => "Delete_Housemanship_Posting_Applications", "description" => "Allow the user to delete housemanship posting applications"],
            ["name" => "Create_Or_Update_Housemanship_Posting_Applications", "description" => "Allow the user to create or update housemanship posting applications"],

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
