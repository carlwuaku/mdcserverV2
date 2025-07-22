<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintRenewalCertificatesOtcmsPermission extends Migration
{
    public function up()
    {
        $permissions = [
            ["name" => "Print_Renewal_Certificates_otcms", "description" => "Allow the user to print renewal certificates for otcms"]


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
