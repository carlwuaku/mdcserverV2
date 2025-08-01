<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertPaymentPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            ["name" => "Update_Payment_Fees", "description" => "Allow the user to add new payment fees"],
            ["name" => "Delete_Payment_Fees", "description" => "Allow the user to delete payment fees"],
            ["name" => "View_Payment_Fees", "description" => "Allow the user to view payment fees"],
            ["name" => "Create_Payment_Fees", "description" => "Allow the user to create payment fees"],


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
