<?php

namespace App\Services;
use App\Helpers\AuthHelper;
use App\Helpers\Types\CriteriaType;
use App\Models\Licenses\LicensesModel;
use App\Helpers\Utils;
use App\Models\SettingsModel;
class PortalService
{
    public function __construct()
    {
    }

    /**
     * Returns the fields for the user profile edit form
     *
     * The fields are determined by the user type and the editable fields
     * set in the setting `portal_editable_fields`.
     *
     * @return array of fields
     */
    public function getUserProfileFields()
    {
        $userId = auth("tokens")->id();
        $user = AuthHelper::getAuthUser($userId);
        $userData = array_merge((array) $user, (array) $user->profile_data);
        $settingsModel = new SettingsModel();
        $editableFieldsSetting = $settingsModel->where('key', SETTING_PORTAL_EDITABLE_FIELDS)->get()->getFirstRow('array');// semi-colon-separated string, if any
        $editableFields = [];
        try {
            $editableFields = $editableFieldsSetting ? unserialize($editableFieldsSetting['value']) : [];
        } catch (\Throwable $th) {
            $editableFields = $editableFieldsSetting ? explode(';', ($editableFieldsSetting['value'])) : [];
        }

        //for licenses, get the fields from license types in app-settings
        if (in_array($user->user_type, USER_TYPES_LICENSED_USERS)) {
            $licenseModel = new LicensesModel();
            $licenseDef = Utils::getLicenseSetting($user->profile_data['type']);
            $allFields = array_merge($licenseDef->fields, $licenseModel->getFormFieldsForPortal());
            //mark editable fields
            foreach ($allFields as $key => $field) {
                $allFields[$key]['editable'] = in_array($field['name'], $editableFields);
                if (array_key_exists($field['name'], $userData)) {
                    $allFields[$key]['value'] = $userData[$field['name']];
                }
            }
            return $allFields;
        } else {
            //TODO: add fields for non-licensed users
            return [];
        }
    }

    /**
     * Gets the value of the system setting with the given name.
     *
     * This will check the criteria of each value in the setting and return the value
     * that matches the user data.
     *
     * @param string $name the name of the system setting
     * @return mixed the value of the system setting
     */
    public function getSystemSetting(string $name)
    {
        $setting = Utils::getSystemSetting($name);
        //check the criteria
        $userId = auth("tokens")->id();
        $user = AuthHelper::getAuthUser($userId);
        $userData = array_merge((array) $user, (array) $user->profile_data);
        $returnValue = null;
        foreach ($setting->value as $value) {
            if (CriteriaType::matchesCriteria((array) $userData, $value->criteria)) {
                $returnValue = $value->value;
                break;
            }
        }

        return $returnValue;
    }
}