<?php
namespace App\Helpers;

use App\Models\UsersModel;
use App\Models\RolePermissionsModel;

class AuthHelper
{
    /**
     * Get the user object with all the details including permissions.
     *
     * @param int $userId The user ID
     * @return UsersModel
     * @throws \Exception
     */
    public static function getAuthUser($userId)
    {
        $userObject = new UsersModel();
        $userData = $userObject->find($userId);
        if (!$userData) {
            throw new \Exception("User not found");
        }
        $usersModel = new UsersModel();
        $user = $usersModel->fromCIUserEntity($userData);
        if ($user->user_type !== 'admin') {
            //get their profile details from their profile table. if it's a license use the LicenseUtils::getLicenseDetails() function
            if (isset($user->profile_table) && isset($user->profile_table_uuid)) {
                if (in_array($user->user_type, USER_TYPES_LICENSED_USERS)) {

                    $user->profile_data = LicenseUtils::getLicenseDetails($user->profile_table_uuid);
                } else {
                    $db = \Config\Database::connect();

                    $profileData = $db->table($user->profile_table)->where(["uuid" => $user->profile_table_uuid])->get()->getFirstRow();
                    if (!empty($profileData)) {
                        //if data are stored in the profile table, get them
                        $user->profile_data = $profileData;
                    }
                }
            }
        }


        return $user;
    }

    /**
     * Get the permissions for the given user.
     * @param UsersModel $userData The user data object
     * @return array<string> The permissions array
     * @throws \Exception
     */
    public static function getAuthUserPermissions(UsersModel $userData)
    {
        $permissionsList = [];
        if ($userData->user_type === 'admin') {
            $rpObject = new RolePermissionsModel();
            $permissions = $rpObject->where("role", $userData->role_name)->groupBy('permission, role')->findAll();

            foreach ($permissions as $permission) {
                $permissionsList[] = $permission['permission'];
            }
        } else {
            //TODO: for non admins use their permissions from the app.settings.json file.
            //also get their profile details from their profile table
        }

        return $permissionsList;
    }

    /**
     * Gets a unique identifier for the authenticated user.
     * 
     * This will use the license number if the user has a profile data
     * with a license number. Otherwise, it will use the user name.
     * 
     * @param string $userId The user ID
     * @return string The unique identifier
     */
    public static function getAuthUserUniqueId($userId)
    {
        $user = self::getAuthUser($userId);
        //if the user has profile data, use the license number. else use the user name
        $uniqueId = isset($user->profile_data['license_number']) ? $user->profile_data['license_number'] : $user->username;
        return $uniqueId;
    }

}