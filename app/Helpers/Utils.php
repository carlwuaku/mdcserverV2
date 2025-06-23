<?php

namespace App\Helpers;

use CodeIgniter\Database\BaseBuilder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;
use \DateTime;
use App\Models\Cpd\CpdModel;
use App\Models\Cpd\ExternalCpdsModel;
use App\Models\Cpd\CpdAttendanceModel;
use App\Helpers\Enums\HousemanshipSetting;


class Utils
{

    // Path to your certificate files - store these securely!
    private static $privateKeyPath = ROOTPATH . 'certs/private_key.pem';
    private static $publicKeyPath = ROOTPATH . 'certs/public_key.pem';

    /**
     * Get the absolute path to the app settings file
     * @return string
     */
    public static function getAppSettingsFileName()
    {
        $fileName = getenv('APP_SETTINGS_FILE') ?? 'app-settings.json';
        if (!file_exists(ROOTPATH . $fileName)) {
            log_message('error', "Settings file not found: $fileName");
            throw new \Exception("No settings file found", 1);

        }
        return ROOTPATH . $fileName;
    }

    /**
     * Compares two objects and returns the keys with different values.
     *
     * @param mixed $oldObject The first object to compare
     * @param mixed $newObject The second object to compare
     * @return array The keys with different values between the two objects
     */
    public static function compareObjects($oldObject, $newObject)
    {
        try {
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
        } catch (\Throwable $th) {
            log_message('error', "Error comparing objects: " . $th);
            return [];
        }

    }


    /**
     * Generate QR code
     * @param string $qrText
     * @param bool $saveFile
     * @param string $filename
     * @return string The path to the generated QR code image if $saveFile is true, otherwise the data URI
     */
    public static function generateQRCode(string $qrText, bool $saveFile, string $filename = ""): string
    {
        $writer = new PngWriter();

        // Create QR code
        $qrCode = new QrCode(
            data: $qrText,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 100,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),

        );

        // Create generic logo
        $logoPath = FCPATH . 'assets/images/logo.png';// PUBLICPATH . '/assets/images/logo.png';
        $logo = new Logo(
            path: $logoPath,
            resizeToWidth: 50,
            punchoutBackground: false
        );

        // Create generic label
        $label = new Label(
            text: 'Label',
            textColor: new Color(255, 0, 0)
        );

        $result = $writer->write($qrCode);

        // Validate the result
        // $writer->validateResult($result, $qrText);


        // $writer->validateResult($result, $qrText);
        if ($saveFile) {
            // Save it to a file
            $mimetype = "png";
            $file_name = empty($filename) ? uniqid() . ".$mimetype" : $filename . ".$mimetype";
            //if the folder does not exist, create it
            if (!file_exists(WRITEPATH . QRCODES_ASSETS_FOLDER)) {
                mkdir(WRITEPATH . QRCODES_ASSETS_FOLDER);
            }
            $path = WRITEPATH . QRCODES_ASSETS_FOLDER . DIRECTORY_SEPARATOR . "$file_name";
            $result->saveToFile($path);
            return $path;
        }
        // Generate a data URI to include image data inline (i.e. inside an <img> tag)
        $dataUri = $result->getDataUri();
        return $dataUri;
    }


    public static function fillTemplate()
    {
    }

    public static function generateApplicationCode($formType)
    {
        $year = date('y');
        $prefix = strtoupper(substr($formType, 0, 3));

        $suffix = bin2hex(random_bytes(16)) . $year;
        return $prefix . $suffix;
    }

    /**
     * get the value for a key in app settings
     * @param string $license
     * @return array
     */
    public static function getAppSettings(string $key = null): ?array
    {
        /**
         * @var array
         */
        $data = json_decode(file_get_contents(self::getAppSettingsFileName()), true);
        if ($key) {
            return $data[$key] ?? null;
        }
        return $data;
    }

    public static function setAppSettings(string $key, $value)
    {
        $data = json_decode(file_get_contents(self::getAppSettingsFileName()), true);
        $data[$key] = $value;
        file_put_contents(self::getAppSettingsFileName(), json_encode($data));
    }

    /**
     * get the table name, fields, other settings for a license type
     * @param string $license
     * @return object {table: string, uniqueKeyField: string,selectionFields:array, displayColumns: array, fields: array, onCreateValidation: array, 
     * onUpdateValidation: array, renewalFields: array, renewalTable: string, renewalStages: object, 
     * fieldsToUpdateOnRenewal: array, basicStatisticsFields: array,
     *  basicStatisticsFilterFields: array, advancedStatisticsFields: array, renewalFilterFields: array, 
     *  renewalBasicStatisticsFields: array, renewalSearchFields: array, gazetteTableColumns: array, renewalJsonFields: array}
     */
    public static function getLicenseSetting(string $license): object
    {
        $licenses = self::getAppSettings("licenseTypes");
        if (!$licenses || !array_key_exists($license, $licenses)) {
            throw new \Exception("License not found");
        }
        return (object) $licenses[$license];
    }

    /**
     * get the fields defined in app.settings.json for a license type.
     * @param string $license
     * @return array
     */
    public static function getLicenseFields(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->fields;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * get the table name for a license type
     * @param string $license
     * @return string
     */
    public static function getLicenseTable(string $license): string
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->table;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * get the validation rules defined in app.settings.json for a license type when creating a license.
     * @param string $license
     * @return array
     */
    public static function getLicenseOnCreateValidation(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->onCreateValidation;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * get the validation rules defined in app.settings.json for a license type when updating a license.
     * @param string $license
     * @return array
     */

    public static function getLicenseOnUpdateValidation(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            return $licenseDef->onUpdateValidation;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * generate codeigniter validation rules from the config in app.settings.json for a specified license type.
     * @param string $license
     * @param string $stage
     * @return array
     */
    public static function getLicenseRenewalStageValidation(string $license, string $stage): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            /** @var array {label: string, name: string, hint: string, options: array, type: string, value: string, required: bool} */

            $fields = $licenseDef->renewalStages[$stage]['fields'];
            log_message('info', "Fields: " . $stage . $license . json_encode($fields));
            return self::getRulesFromFormGeneratorFields($fields);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * generate codeigniter validation rules from form generator fields.
     * it returns an array of rules in the format: 
     * [
     *          "license_number" => "required|is_unique[licenses.license_number]",
     *         "registration_date" => "required|valid_date",
     * ]
     * @param array $fields
     * @return array
     */
    public static function getRulesFromFormGeneratorFields(array $fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $fieldRules = [];
            if ($field['required']) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'permit_empty';
                $fieldRules[] = 'if_exist';
            }
            if ($field['type'] === 'date') {
                $fieldRules[] = 'valid_date';
            }
            $rules[$field['name']] = implode("|", $fieldRules);
        }
        return $rules;
    }

    /**
     * This function reorders the fields in a form/header list to have certain fields at the top. Wherever the priority fields are 
     * present, the should be at the top of the list. 
     * @param array $columns
     * @return array
     */
    public static function reorderPriorityColumns(array $columns): array
    {
        $priorityColumns = [];
        $otherColumns = [];
        foreach ($columns as $column) {
            if (in_array($column, PRIORITY_FIELDS)) {
                if (!in_array($column, $priorityColumns))
                    $priorityColumns[] = $column;
            } else {
                if (!in_array($column, $otherColumns))
                    $otherColumns[] = $column;
            }
        }
        //return unique values

        return array_merge($priorityColumns, $otherColumns);
    }

    /**
     * get the validation rules defined in app.settings.json for a license type when creating a license.
     * @param string $license
     * @return array
     */
    public static function getLicenseSearchFields(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            if (property_exists($licenseDef, 'searchFields')) {
                return $licenseDef->searchFields;
            }
            throw new \Exception("Search fields not defined for $license");
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * get the validation rules defined in app.settings.json for a license type when creating a license.
     * @param string $license
     * @return array
     */
    public static function getLicenseRenewalSearchFields(string $license): array
    {
        try {
            $licenseDef = self::getLicenseSetting($license);
            if (property_exists($licenseDef, 'renewalSearchFields')) {
                return $licenseDef->renewalSearchFields;
            }
            throw new \Exception("Renewal search fields not defined for $license");
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function generateSecureDocument($documentData)
    {

        // Generate unique document identifier
        $documentId = bin2hex(random_bytes(16));



        // Generate verification token
        $token = self::generateVerificationToken($documentId);
        $verificationUrl = site_url(relativePath: "verify/{$token}");
        $qrPath = Utils::generateQRCode($verificationUrl, true);
        return $qrPath;
    }

    /**
     * Generate verification token
     */
    public static function generateVerificationToken($documentId): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Sign document data using private key
     */
    public static function signDocument($data): string
    {
        $privateKey = openssl_pkey_get_private(file_get_contents(self::$privateKeyPath));
        $signature = '';
        openssl_sign(json_encode($data), $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    public function getMonthName($month)
    {
        switch ($month) {
            case "1":
                $name = "January";
                break;
            case "2":
                $name = "February";
                break;
            case "3":
                $name = "March";
                break;
            case "4":
                $name = "April";
                break;
            case "5":
                $name = "May";
                break;
            case "6":
                $name = "June";
                break;
            case "7":
                $name = "July";
                break;

            case "8":
                $name = "August";
                break;
            case "9":
                $name = "September";
                break;
            case "10":
                $name = "October";
                break;
            case "11":
                $name = "November";
                break;
            case "12":
                $name = "December";
                break;

            default:
                $name = "N/A";
                break;
        }
        return $name;
    }

    public function getAge($date)
    {
        //date in yyyy-mm-dd format
        if ($date === "" || $date === null || $date === "0000-00-00") {
            return 0;
        }
        //explode the date to get month, day and year
        $birthDate = explode("-", $date);
        //get age from date or birthdate
        $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[2], $birthDate[1], $birthDate[0]))) > date("md")
            ? ((date("Y") - $birthDate[0]) - 1)
            : (date("Y") - $birthDate[0]));
        return $age;
    }

    public static function getYearDifference($start, $end)
    {
        //get the years from the dates. the start should always be earlier than the end
        $start_year = date("Y", strtotime($start));
        $end_year = date("Y", strtotime($end));
        return $end_year - $start_year;
    }

    public static function addMonths($date, $months)
    {
        return date('Y-m-d', strtotime("+$months months", strtotime($date)));
    }

    public static function addDays($date, $days)
    {
        return date('Y-m-d', strtotime("+$days days", strtotime($date)));
    }

    public static function addPeriod($date, $days)
    {
        return date('Y-m-d', strtotime("$days", strtotime($date)));
    }

    public static function getDaysDifference($start, $end = null)
    {
        $start_date = new DateTime($start);
        $end_date = !$end ? new DateTime() : new DateTime($end);
        $interval = $start_date->diff($end_date);
        return $interval->days;
    }

    /**
     * SINCE THE MDC REQUIRES A MINIMUM TO BE ATTAINED IN EACH CATEGORY, USING THE AGE TO ADD POINTS WILL NOT MAKE ANY DIFFERENCE.
     * SAME GOES FOR THE FLAGS. SO WE WILL RATHER TAKE THEM OUT FROM HERE AND ADD THE EXCEPTIONS TO THE PERMIT-RETENTION SYSTEM.
     * THAT WILL CREATE A COMPLETE BYPASS OF THE CPD REQUIREMENT, INSTEAD OF TRYING TO ADD ON POINTS BECAUSE OF AGE.
     * @param string $licenseNumber
     * @param string $year
     * @return array{score: int, attendance: array{attendance_date: string, topic: string, credits: int, provider_name:string, provider_uuid:string, provider_type:string, category: int, venue: string}[]} 
     */
    public static function getCPDAttendanceAndScores($licenseNumber, $year)
    {
        try {

            $sum_total = 0;
            $sum_normal = 0;
            $sum_external = 0;
            $model = new CpdAttendanceModel();
            $cpdModel = new CpdModel();
            $externalModel = new ExternalCpdsModel();
            // log_message('info', "year: " . $year);
            $person = LicenseUtils::getLicenseDetails($licenseNumber);
            $isForeign = array_key_exists("country_of_practice", $person) && strtolower($person['country_of_practice']) !== "ghana";
            $provisionalNumber = null;
            if (
                $person && array_key_exists("register_type", $person) && strtolower($person['register_type']) === "permanent"
                && array_key_exists("provisional_number", $person)
                && !empty($person['provisional_number'])
            ) {
                $provisionalNumber = $person['provisional_number'];
            }
            $builder = $model->builder();
            $builder = $model->addCustomFields($builder);

            $builder->groupStart()->where("{$model->table}.license_number", $licenseNumber);
            //if the person is permanent, add the records from their provisional data to the list
            if (!empty($provisionalNumber)) {
                $builder->orWhere("{$model->table}.license_number", $person['provisional_number']);
            }
            $builder->groupEnd();
            //cpd_date comes from addCustomFields
            $builder->where("year({$cpdModel->table}.date)", $year);

            // log_message('info', "CPD Query: " . $builder->getCompiledSelect(false));
            $cpds = $builder->get()->getResult('array');

            $externalBuilder = $externalModel->builder();
            $externalBuilder->where("{$externalModel->table}.license_number", $licenseNumber);
            if (!empty($provisionalNumber)) {
                $externalBuilder->orWhere("{$externalModel->table}.license_number", $person['provisional_number']);
            }
            if ($year != "") {
                $externalBuilder->where("year({$externalModel->table}.attendance_date)", $year);
            }
            $externalCpds = $externalBuilder->get()->getResult('array');



            $records = array_merge($cpds, $externalCpds);
            // log_message('info', "CPD Records: " . json_encode($records));
            $response = array();
            foreach ($records as $value) {
                //create attendance obj
                $object = array();
                //external cpds don't have a cpd_date. so we use the attendance date
                $cpd_year = (int) date("Y", strtotime($value['cpd_date'] ?? $value['attendance_date']));
                $attendance_year = (int) date("Y", strtotime($value['attendance_date']));
                $object['attendance_date'] = $attendance_year != $cpd_year ? $value['cpd_date'] : $value['attendance_date'];
                $object['topic'] = $value['topic'];
                $object['credits'] = $value['credits'];

                if (array_key_exists('provider_uuid', $value) && !empty($value['provider_uuid'])) {
                    $object['provider_uuid'] = $value['provider_uuid'];
                    $object['provider_name'] = $value['provider_name'];
                    $object['provider_type'] = 'internal';
                    $sum_normal += $value['credits'];
                } else {
                    $object['provider_uuid'] = null;
                    $object['provider_name'] = array_key_exists('provider', $value) ? $value['provider'] : 'N/A';
                    $object['provider_type'] = 'external';
                    $sum_external += $value['credits'];
                }
                $object['category'] = array_key_exists('category', $value) ? $value['category'] : 1;
                $object['venue'] = array_key_exists('venue', $value) ? $value['venue'] : "N/A";

                $response[] = $object;
            }
            if ($isForeign) {
                $sum_total = $sum_normal + $sum_external;
            } else {
                //for local practitioners, they can only earn 5 points from external cpds. so we limit the number of points
                $sum_total = $sum_normal + ($sum_external > 5 ? 5 : $sum_external);
            }

            return ["score" => $sum_total, "attendance" => $response];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get the start and end dates from a date range string.
     * @param string $dateRange
     * @return array{start: string, end: string}
     */
    public static function getDateRange($dateRange)
    {
        if (strpos($dateRange, ' to ') !== false) {
            list($start, $end) = explode(' to ', $dateRange);
            return [
                'start' => $start,
                'end' => $end
            ];
        }
        return [
            'start' => $dateRange,
            'end' => $dateRange
        ];
    }

    /**
     * get a setting for housemanship. could be availabilityCategories
     * @return array
     */
    public static function getHousemanshipSetting(HousemanshipSetting $setting): array
    {
        $result = self::getAppSettings("housemanship");
        if (!$result || !array_key_exists($setting->value, $result)) {
            throw new \Exception("$setting->value not found in housemanship settings");
        }
        return $result[$setting->value];
    }


    /**
     * attempt to parse a parameter as JSON. If it is valid JSON, return the decoded value.
     * If it is not valid JSON, return the original value.
     * @param string $param
     * @return mixed
     */
    public static function parseParam($param)
    {
        // If it's not a string, just return it as-is
        if (!is_string($param)) {
            return $param;
        }

        // Trim the string
        $param = trim($param);

        // Check if it looks like JSON (starts with { or [)
        if (
            (substr($param, 0, 1) == '{' && substr($param, -1) == '}') ||
            (substr($param, 0, 1) == '[' && substr($param, -1) == ']')
        ) {

            // Try to decode it
            $decoded = json_decode($param, true);

            // If the decode was successful, return the decoded value
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // If it doesn't look like JSON or couldn't be decoded, return original
        return $param;
    }

    /**
     * add a where clause to a query builder depending on the value type. It will check if the value is an array, a date range, or a null value.
     * @param BaseBuilder $builder
     * @param string $columnName
     * @param mixed $value
     * @return BaseBuilder
     */
    public static function parseWhereClause(BaseBuilder $builder, $columnName, $value): BaseBuilder
    {
        if (is_array($value)) {
            if (!empty($value)) {
                // Check if the array contains special values
                $nullExists = in_array('--Null--', $value);
                $emptyExists = in_array('--Empty Value--', $value);

                // Filter out special values to get regular values
                $regularValues = array_filter($value, function ($item) {
                    return $item !== '--Null--' && $item !== '--Empty Value--';
                });

                // If we have special values, we need to build a complex WHERE clause
                if ($nullExists || $emptyExists) {
                    $builder->groupStart(); // Start grouping conditions with parentheses

                    if (!empty($regularValues)) {
                        $builder->whereIn($columnName, $regularValues);
                    }

                    if ($nullExists) {
                        if (!empty($regularValues)) {
                            $builder->orWhere($columnName . ' IS NULL');
                        } else {
                            $builder->where($columnName . ' IS NULL');
                        }
                    }

                    if ($emptyExists) {
                        if (!empty($regularValues) || $nullExists) {
                            $builder->orWhere($columnName . ' = ""');
                        } else {
                            $builder->where($columnName . ' = ""');
                        }
                    }

                    $builder->groupEnd(); // End grouping
                } else {
                    // No special values, just use regular whereIn
                    $builder->whereIn($columnName, $value);
                }
            }
        } else {
            // Single value logic remains the same
            if (strpos($columnName, 'date') !== false) {
                $dateRange = Utils::getDateRange($value);
                $builder->where($columnName . ' >=', $dateRange['start']);
                $builder->where($columnName . ' <=', $dateRange['end']);
            } else if ($value === "--Null--") {
                $builder->where($columnName . ' IS NULL');
            } else if ($value === "--Empty Value--") {
                $builder->where($columnName . ' = ""');
            } else {
                $builder->where($columnName, $value);
            }
        }
        return $builder;
    }

    /**
     * Filters an array by keys.
     *
     * The function takes an associative array and an array of keys as input.
     * It returns a new associative array which contains only the key-value pairs
     * from the input array where the key is one of the given keys.
     *
     * @param array $array The input array to be filtered.
     * @param array $keys The array of keys to filter by.
     * @return array The filtered array.
     */
    public static function filterArrayByKeys(array $array, array $keys): array
    {
        return array_filter(
            $array,
            function ($key) use ($keys) {
                return in_array($key, $keys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
    /**
     * Filters an array by keys, excluding the specified keys.
     *
     * The function takes an associative array and an array of keys as input.
     * It returns a new associative array which contains only the key-value pairs
     * from the input array where the key is not one of the given keys.
     *
     * @param array $array The input array to be filtered.
     * @param array $keys The array of keys to exclude.
     * @return array The filtered array excluding specified keys.
     */
    public static function filterOutArrayByKeys(array $array, array $keys): array
    {
        return array_filter(
            $array,
            function ($key) use ($keys) {
                return !in_array($key, $keys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
