<?php
namespace App\Helpers;



class Utils
{

    /**
     * Compares two objects and returns the keys with different values.
     *
     * @param mixed $oldObject The first object to compare
     * @param mixed $newObject The second object to compare
     * @return array The keys with different values between the two objects
     */
    public static function compareObjects($oldObject, $newObject)
    {
        if (is_array($oldObject)) {
            $oldObject = (object) $oldObject;
        }

        if (is_array($newObject)) {
            $newObject = (object) $newObject;
        }
        $obj1Vars = get_object_vars($oldObject);
        $obj2Vars = get_object_vars($newObject);
        $differentKeys = [];
        foreach ($obj2Vars as $key => $value) {
            if ($key !== "qr_code" && isset($obj1Vars[$key]) && $obj1Vars[$key] !== $value) {
                $differentKeys[] = $key . ": {$obj1Vars[$key]} -> $value";
            }
        }

        return $differentKeys;
    }




    public static function fillTemplate()
    {
    }

    public static function generateApplicationCode($formType)
    {
        $year = date('y');
        $prefix = strtoupper(substr($formType, 0, 3));
        $suffix = strtoupper(substr(uniqid(), 0, 6)) . $year;
        return $prefix . $suffix;
    }

    public static function getAppSettings(string $key = null): ?array
    {
        /**
         * @var array
         */
        $data = json_decode(file_get_contents(ROOTPATH . 'app-settings.json'), true);
        if ($key) {
            return $data[$key] ?? null;
        }
        return $data;
    }

    public static function setAppSettings(string $key, $value)
    {
        $data = json_decode(file_get_contents(ROOTPATH . 'app-settings.json'), true);
        $data[$key] = $value;
        file_put_contents(ROOTPATH . 'app-settings.json', json_encode($data));
    }

    /**
     * get the table name, fields, other settings for a license type
     * @param string $license
     * @return object {table: string, fields: array, onCreateValidation: array, onUpdateValidation: array}
     */
    public static function getLicenseSetting(string $license): object
    {
        $licenses = self::getAppSettings("licenseTypes");
        if (!$licenses || !array_key_exists($license, $licenses)) {
            throw new \Exception("License not found");
        }
        return (object) $licenses[$license];
    }

    public static function getLicenseFields(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->fields;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public static function getLicenseTable(string $license): string
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->table;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public static function getLicenseOnCreateValidation(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->onCreateValidation;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public static function getLicenseOnUpdateValidation(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->onUpdateValidation;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

}
