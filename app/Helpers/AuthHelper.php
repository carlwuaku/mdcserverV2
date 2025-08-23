<?php
namespace App\Helpers;
use App\Helpers\Types\Alert;
use App\Helpers\Types\CriteriaType;
use App\Helpers\Types\PortalHomeConfigType;
use App\Models\UsersModel;
use App\Models\RolePermissionsModel;
use CodeIgniter\Shield\Entities\User;
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

    public static function fillPortalHomeMenuForUser(User $user, PortalHomeConfigType $portalHomeConfig)
    {

        //strings may have variables in them like [var_name]. replace them
        try {
            $templateEngine = new TemplateEngineHelper();
            $userData = array_merge([$user->display_name, $user->email_address], (array) $user->profile_data);
            $portalHomeConfig->title = $templateEngine->process($portalHomeConfig->title, $userData);
            $portalHomeConfig->description = $templateEngine->process($portalHomeConfig->description, $userData);
            $portalHomeConfig->image = $templateEngine->process($portalHomeConfig->image, $userData);
            //if the image is not a full url, add the base url to it
            if (strpos($portalHomeConfig->image, "http") === false) {
                $portalHomeConfig->image = base_url($portalHomeConfig->image);
            }
            //for the alerts, include them if the user matches the criteria
            $alerts = [];
            foreach ($portalHomeConfig->alerts as $alert) {
                if (CriteriaType::matchesCriteria($userData, $alert->criteria)) {
                    $alerts[] = new Alert(
                        $templateEngine->process($alert->message, $userData),
                        $alert->type
                    );
                }
            }
            $portalHomeConfig->alerts = $alerts;

            return $portalHomeConfig;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}