<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertPermissions extends Migration
{
    public function up()
    {

        $permissions = [
            ["name" => "Create_Or_Edit_User_Role", "description" => "Allow the user to create a new role such as \'Finance officers\' or \'Admins\' and assign permissions to them"],
            ["name" => "View_User_Roles", "description" => "Allow the user to view all the roles in the system"],
            ["name" => "Delete_User_Role", "description" => "Allow the user to delete user roles"],
            ["name" => "Create_Or_Delete_User_Permissions", "description" => "Allow the user to add or remove permissions from user roles"],
            ["name" => "Create_Or_Edit_User", "description" => "Allow the user to add or edit users in the system"],
            ["name" => "Activate_Or_Deactivate_User", "description" => "Allow the user to activate or deactivate users in the system"],
            ["name" => "View_Users", "description" => "Allow the user to view all the users in the system"],
            ["name" => "Delete_User", "description" => "Allow the user to delete users in the system"],
            ["name" => "Modify_Settings", "description" => "Allow the user to modify system-wide settings"],
            ["name" => "View_Settings", "description" => "Allow the user to view system-wide settings"],
            ["name" => "Create_Api_User", "description" => "Allow the user to create API keys for external access"],
            ["name" => "Create_Or_Edit_Assets", "description" => "Allow the user to upload files"],
            ["name" => "Send_Email", "description" => "Allow the user to send emails"],
            ["name" => "Update_Application_Forms", "description" => "Allow the user to update the status of submitted application forms. Some application forms may still be restricted to certain roles"],
            ["name" => "Delete_Application_Forms", "description" => "Allow the user to delete submitted applications"],
            ["name" => "View_Application_Forms", "description" => "Allow the user to view the details of submitted application forms"],
            ["name" => "Create_Application_Forms", "description" => "Allow the user to submit applications"],
            ["name" => "Restore_Application_Forms", "description" => "Allow the user to restore deleted applications"],
            ["name" => "View_Application_Form_Templates", "description" => "Allow the user to view the details of application form templates"],
            ["name" => "Update_Application_Form_Templates", "description" => "Allow the user to update the details of application form templates"],
            ["name" => "Delete_Application_Form_Templates", "description" => "Allow the user to delete application form templates"],
            ["name" => "Create_Application_Form_Templates", "description" => "Allow the user to add new application form templates"],
            ["name" => "Update_License_Details", "description" => "Allow the user to update the details of licenses"],
            ["name" => "Delete_License_Details", "description" => "Allow the user to send emails"],
            ["name" => "View_License_Details", "description" => "Allow the user to delete licenses"],
            ["name" => "Create_License_Details", "description" => "Allow the user to add new licenses"],
            ["name" => "Restore_License_Details", "description" => "Allow the user to restore deleted licenses"],
            ["name" => "Update_License_Renewal", "description" => "Allow the user to update the details of a renewal"],
            ["name" => "Delete_License_Renewal", "description" => "Allow the user to delete a license renewal"],
            ["name" => "View_License_Renewal", "description" => "Allow the user to view license renewals"],
            ["name" => "Create_License_Renewal", "description" => "Allow the user to create a new license renewal"],
            ["name" => "Update_CPD_Details", "description" => "Allow the user to update the details of a CPD"],
            ["name" => "Delete_CPD_Details", "description" => "Allow the user to delete CPDs"],
            ["name" => "Restore_CPD_Details", "description" => "Allow the user to restore a deleted CPD"],
            ["name" => "Create_CPD_Details", "description" => "Allow the user to add a new CPD"],
            ["name" => "View_CPD_Details", "description" => "Allow the user to view the details of a CPD or list of CPDs"],
            ["name" => "Update_CPD_Providers", "description" => "Allow the user to update the details of a CPD provider"],
            ["name" => "Delete_CPD_Providers", "description" => "Allow the user to delete CPD providers"],
            ["name" => "View_CPD_Providers", "description" => "Allow the user to view the details of a CPD provider"],
            ["name" => "Create_CPD_Providers", "description" => "Allow the user to add a new CPD provider"],
            ["name" => "Restore_CPD_Providers", "description" => "Allow the user to restore a deleted CPD provider"],
            ["name" => "Update_CPD_Attendance", "description" => "Allow the user to update the details of CPD attendance"],
            ["name" => "Delete_CPD_Attendance", "description" => "Allow the user to delete CPD attendance"],
            ["name" => "View_CPD_Attendance", "description" => "Allow the user to view the details of CPD attendance"],
            ["name" => "Create_CPD_Attendance", "description" => "Allow the user to add a new CPD attendance"],
            ["name" => "Restore_CPD_Attendance", "description" => "Allow the user to restore a deleted CPD attendance"]
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
