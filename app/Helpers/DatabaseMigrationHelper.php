<?php
namespace App\Helpers;

class DatabaseMigrationHelper
{
    protected $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Adds a list of permissions to the database and assigns them to the Developer role.
     *
     * This function takes an array of permissions, each containing a name and description,
     * and inserts them into the permissions table with an active status. It ensures that the
     * Developer role exists and assigns all the newly added permissions to this role.
     *
     * @param array{name: string, description: string} $permissions An array of permissions where each permission is an associative
     *                           array with 'name' and 'description' keys.
     */

    public function addPermissions(array $permissions)
    {

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
}
