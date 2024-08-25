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

}
