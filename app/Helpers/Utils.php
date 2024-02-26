<?php
namespace App\Helpers;

class Utils{
    /**
     * Compares two objects and returns the keys with different values.
     *
     * @param mixed $oldObject The first object to compare
     * @param mixed $newObject The second object to compare
     * @return array The keys with different values between the two objects
     */
    public static function compareObjects($oldObject, $newObject) {
        if(is_array($oldObject)){
            $oldObject = (object) $oldObject;
        }
        
        if(is_array($newObject)){
            $newObject = (object) $newObject;
        }
        $obj1Vars = get_object_vars($oldObject);
        $obj2Vars = get_object_vars($newObject);
        $differentKeys = [];
        log_message("error", "Utils: compareObjects: ".print_r($obj1Vars, true).print_r($obj2Vars, true));
        foreach ($obj2Vars as $key => $value) {
            if (isset($obj1Vars[$key]) && $obj1Vars[$key] !== $value) {
                $differentKeys[] = $key.": $value -> {$obj1Vars[$key]}";
            }
        }
    
        return $differentKeys;
    }
}
 