<?php
namespace App\Helpers\Types;

class TrainingInstitutionSettingsType
{
    /**
     * 
     * @var KeyValueType[]
     */
    public static $practitionerTypes;

    public function __construct(array $practitionerTypes)
    {
        self::$practitionerTypes = $practitionerTypes;
    }

    public static function fromArray($array)
    {
        $practitionerTypes = [];
        if (array_key_exists('practitioner_types', $array)) {
            try {
                $practitionerTypes = array_map(
                    function ($item) {
                        if (!array_key_exists('key', $item) || !array_key_exists('value', $item)) {
                            throw new \Exception("Invalid practitioner type");
                        }
                        return new KeyValueType($item['key'], $item['value']);
                    },
                    $array['practitioner_types']
                );
            } catch (\Throwable $th) {
                log_message('error', $th);
            }
        }
        return new TrainingInstitutionSettingsType($practitionerTypes);
    }
}