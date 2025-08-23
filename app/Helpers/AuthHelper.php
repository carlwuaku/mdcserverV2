<?php
namespace App\Helpers;

use App\Models\UsersModel;
use App\Models\RolePermissionsModel;

class AuthHelper
{
    public static function getAuthUser($userId)
    {
        $userObject = new UsersModel();
        $userData = $userObject->find($userId);
        if (!$userData) {
            throw new \Exception("User not found");
        }
        $permissionsList = [];
        //for admins use their roles to get permissions
        if ($userData->user_type === 'admin') {
            $rpObject = new RolePermissionsModel();
            $permissions = $rpObject->where("role", $userData->role_name)->findAll();

            foreach ($permissions as $permission) {
                $permissionsList[] = $permission['permission'];
            }
        } else {
            //TODO: for non admins use their permissions from the app.settings.json file.
            //also get their profile details from their profile table. if it's a license use the LicenseUtils::getLicenseDetails() function
            if (isset($userData->profile_table) && isset($userData->profile_table_uuid)) {
                if (in_array($userData->user_type, USER_TYPES_LICENSED_USERS)) {
                    $userData->profile_data = LicenseUtils::getLicenseDetails($userData->profile_table_uuid);
                } else {
                    $db = \Config\Database::connect();

                    $profileData = $db->table($userData->profile_table)->where(["uuid" => $userData->profile_table_uuid])->get()->getFirstRow();
                    if (!empty($profileData)) {
                        //if data are stored in the profile table, get them
                        $userData->profile_data = $profileData;
                    }
                }
            }

        }
        $userData->permissions = $permissionsList;
        return $userData;
    }

}