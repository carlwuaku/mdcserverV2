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
use App\Models\Licenses\LicenseRenewalModel;
use App\Models\Licenses\LicensesModel;
use Exception;
use CodeIgniter\Exceptions\ConfigException;
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
            throw new Exception("No settings file found", 1);

        }
        return ROOTPATH . $fileName;
    }

    /**
     * Get the contents of a file in the app/Templates folder
     * @param string $fileName
     * @return string
     * @throws \Exception
     */
    public static function getTemplateFileContent($fileName)
    {
        //get the contents of a file in the app/Templates folder
        $file = APPPATH . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($file)) {
            log_message('error', "Template file not found: $file");
            throw new \Exception("Template file not found", 1);
        }
        return file_get_contents($file);
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
                    $oldValue = is_array($obj1Vars[$key]) ? json_encode($obj1Vars[$key]) : $obj1Vars[$key];
                    $newValue = is_array($value) ? json_encode($value) : $value;
                    $differentKeys[] = $key . ": $oldValue -> $newValue";
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
     * @param string $key
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

    /**
     * Retrieve multiple app settings based on an array of keys.
     *
     * @param array $keys An array of keys to fetch from the app settings.
     * @return array|null An associative array of key-value pairs from the app settings, 
     *                    or null if the settings file cannot be read.
     */

    public static function getMultipleAppSettings(array $keys): ?array
    {
        /**
         * @var array
         */
        $data = json_decode(file_get_contents(self::getAppSettingsFileName()), true);
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $data[$key] ?? null;
        }
        return $result;
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
     * onUpdateValidation: array, renewalFields: array, implicitRenewalFields: array, renewalTable: string, renewalStages: object, 
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
     *         "$data_date" => "required|valid_date",
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
            throw new Exception("Search fields not defined for $license");
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
                $person && array_key_exists("register_type", $person) && $person['register_type'] !== null && strtolower($person['register_type']) === "permanent"
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
            if (self::fieldIsDateField($columnName)) {
                log_message('info', "Date range: " . $columnName);

                $dateRange = Utils::getDateRange($value);
                $builder->where($columnName . ' >=', $dateRange['start']);
                $builder->where($columnName . ' <=', $dateRange['end']);
            } else if ($value === "--Null--") {
                $builder->where($columnName . ' IS NULL');
            } else if ($value === "--Empty Value--") {
                $builder->where($columnName . ' = ""');
            } else if ($value === "--Not Null--") {
                $builder->where($columnName . ' IS NOT NULL AND ' . $columnName . ' != ""');
            } else if ($value === "--Null Or Empty--") {
                $builder->where($columnName . ' IS NULL OR ' . $columnName . ' = ""');
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

    /**
     * Create a table from a two-dimensional array.
     *
     * The function takes a PhpWord object, a two-dimensional array of data, and an optional array of column widths.
     * It returns a Table object.
     *
     * The first row of the array is used as the header row of the table.
     * The function will automatically capitalize the header row column names and replace any underscores with spaces.
     * The column widths are set based on the optional array of column widths. If a column width is not specified, it defaults to 2000.
     *
     * The function will add each row of the array to the table as a new row.
     * If the value of a cell is null, it is replaced with an empty string.
     *
     * @param \PhpOffice\PhpWord\PhpWord $phpWord The PhpWord object to add the table to.
     * @param array $data A two-dimensional array of data to add to the table.
     * @param array $headers An optional array of column headers.
     * @param array $columnWidths An optional array of column widths.
     * @return void.
     */
    public static function createTableFromArray($phpWord, $data, $headers = [], $columnWidths = null)
    {
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
        ]);

        $table = $section->addTable(
            [
                'width' => '100',
                'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT
            ]
        );

        if (empty($data)) {
            return;
        }

        // Calculate column width based on number of columns
        $columnCount = count((array) $data[0]);
        $availableWidth = (11 * 1440); // Total available width in twips
        $defaultWidth = intval($availableWidth / $columnCount);


        // Add header row
        $headerRow = $table->addRow();
        $headers = $headers ?: array_keys($data[0]);

        foreach ($headers as $header) {
            $width = is_array($columnWidths) ? ($columnWidths[$header] ?? $defaultWidth) : $defaultWidth;
            $headerRow->addCell($width)->addText(ucfirst(str_replace('_', ' ', $header)));
        }

        // Add data rows
        foreach ($data as $item) {
            $row = $table->addRow();
            foreach ($headers as $header) {
                $value = property_exists($item, $header) ? $item->$header : "";
                $width = is_array($columnWidths) ? ($columnWidths[$header] ?? $defaultWidth) : $defaultWidth;
                $row->addCell($width)->addText($value ?? '');
            }
        }

        return;
    }

    /**
     * Wrap the given letter content with some default styling
     * This function fetches the letterContainer template from the app settings and replaces the [##content##] placeholder with the given content
     * @param string $content
     * @return string
     */
    public static function addLetterStyling(string $content)
    {
        /** the letterContainer is an array with an html key that contains the template with some styling and a placeholder [##content##] that will be replaced with the letter content
         * @var string
         */
        $template = self::getTemplateFileContent("letter-container.html");
        return str_replace("[##content##]", $content, $template);
    }

    public static function fieldIsDateField(string $fieldName): bool
    {//some fields may be qualified by their table name e.g. license.expiry_date so we need to check for that
        if (strpos($fieldName, '.') !== false) {
            $arr = explode('.', $fieldName);
            $fieldName = array_pop($arr);
        }
        return in_array($fieldName, DATABASE_DATE_FIELDS);
    }

    /**
     * Returns a user-friendly error message based on the given error message from the database
     * If the error is a duplicate entry error, return a more specific message
     * Otherwise, return a generic error message
     * @param string $errorMessage the error message from the database
     * @return string a user-friendly error message
     */
    public static function getUserDatabaseErrorMessage(string $errorMessage)
    {
        //get a sensible but secure message for the UI based on the error message
        //if it's a duplicate entry error, return a more specific message
        if (strpos($errorMessage, "Duplicate entry") !== false) {
            return "Duplicate entry";
        } else {
            return "An error occurred. Please make sure the data is valid and is not a duplicate operation, and try again. ";
        }
    }

    /**
     * Parse MySQL exceptions and return secure but reasonable error messages
     * This function takes raw MySQL error messages and converts them to user-friendly messages
     * while maintaining security by not exposing sensitive database information
     * 
     * @param string $errorMessage The raw MySQL error message
     * @return string A user-friendly error message
     */
    public static function parseMysqlExceptions(string $errorMessage): string
    {
        // Duplicate entry errors - extract the duplicate value and field name
        if (preg_match("/Duplicate entry '(.+?)' for key '(.+?)'/", $errorMessage, $matches)) {
            $duplicateValue = $matches[1];
            $keyName = $matches[2];
            
            // Common key name mappings to user-friendly names
            $fieldMappings = [
                'username' => 'username',
                'email' => 'email address',
                'license_number' => 'license number',
                'phone' => 'phone number',
                'national_id' => 'national ID',
                'passport_number' => 'passport number',
                'registration_number' => 'registration number',
                'PRIMARY' => 'record'
            ];
            
            $friendlyFieldName = $fieldMappings[$keyName] ?? 'value';
            return "The {$friendlyFieldName} '{$duplicateValue}' already exists.";
        }
        
        // Foreign key constraint errors
        if (preg_match("/Cannot add or update a child row: a foreign key constraint fails/", $errorMessage)) {
            return "Cannot perform this operation because it would violate data integrity. Please ensure all referenced data exists.";
        }
        
        if (preg_match("/Cannot delete or update a parent row: a foreign key constraint fails/", $errorMessage)) {
            return "Cannot delete this record because it is referenced by other data. Please remove the related records first.";
        }
        
        // Data truncation errors
        if (preg_match("/Data too long for column '(.+?)'/", $errorMessage, $matches)) {
            $columnName = str_replace('_', ' ', $matches[1]);
            return "The value for '{$columnName}' is too long. Please provide a shorter value.";
        }
        
        // Incorrect data type errors
        if (preg_match("/Incorrect .+ value: '(.+?)' for column '(.+?)'/", $errorMessage, $matches)) {
            $value = $matches[1];
            $columnName = str_replace('_', ' ', $matches[2]);
            return "Invalid value '{$value}' for field '{$columnName}'. Please check the format and try again.";
        }
        
        // Column cannot be null errors
        if (preg_match("/Column '(.+?)' cannot be null/", $errorMessage, $matches)) {
            $columnName = str_replace('_', ' ', $matches[1]);
            return "The field '{$columnName}' is required and cannot be empty.";
        }
        
        // Unknown column errors
        if (preg_match("/Unknown column '(.+?)' in '(.+?)'/", $errorMessage, $matches)) {
            return "Invalid field specified in the request. Please check your data and try again.";
        }
        
        // Table doesn't exist errors
        if (preg_match("/Table '(.+?)' doesn't exist/", $errorMessage)) {
            return "The requested resource is not available. Please contact support.";
        }
        
        // Connection errors
        if (preg_match("/(Connection refused|Can't connect to MySQL server)/", $errorMessage)) {
            return "Database connection error. Please try again later or contact support.";
        }
        
        // Access denied errors
        if (preg_match("/Access denied for user/", $errorMessage)) {
            return "Database access error. Please contact support.";
        }
        
        // Out of range errors
        if (preg_match("/Out of range value for column '(.+?)'/", $errorMessage, $matches)) {
            $columnName = str_replace('_', ' ', $matches[1]);
            return "The value for '{$columnName}' is outside the allowed range.";
        }
        
        // Deadlock errors
        if (preg_match("/Deadlock found when trying to get lock/", $errorMessage)) {
            return "The operation could not be completed due to system contention. Please try again.";
        }
        
        // Lock wait timeout
        if (preg_match("/Lock wait timeout exceeded/", $errorMessage)) {
            return "The operation timed out. Please try again.";
        }
        
        // Default fallback for any other MySQL errors
        return "A database error occurred. Please verify your data is correct and try again. If the problem persists, contact support.";
    }

    /**
     * Get license details by UUID.
     *
     * @param string $uuid The UUID/license number of the license
     * @return array The license data if found, 
     * @throws Exception If license is not found
     */
    public static function getLicenseDetails(string $uuid, string $field = null, string $type = null): array
    {
        $model = new LicensesModel();
        $builder = $model->builder();
        $builder->select($model->getTableName() . '.*');

        $builder = $model->addCustomFields($builder);
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $builder->orWhere($model->getTableName() . '.license_number', $uuid);
        if (!empty($field)) {
            $builder->orWhere($field, $uuid);
        }
        if (!empty($type)) {
            //if a type was provided, do a join to the sub table
            $licenseDef = Utils::getLicenseSetting($type);
            $subTableSelectionFields = $model->getTableName() . '.*';
            if (!$licenseDef) {
                throw new Exception("License type not found in app settings");
            }
            if (!isset($licenseDef->table) || empty($licenseDef->table)) {
                throw new Exception("License table not defined in app settings for type: $type");
            }
            if (!isset($licenseDef->uniqueKeyField) || empty($licenseDef->uniqueKeyField)) {
                throw new Exception("No unique key defined for license type: $type");
            }
            $subtable = $licenseDef->table;
            if (isset($licenseDef->selectionFields) && !empty($licenseDef->selectionFields)) {
                $subTableSelectionFields = implode(',', array_map(function ($fieldName) use ($subtable) {
                    return $subtable . '.' . $fieldName;
                }, $licenseDef->selectionFields));
            }
            $builder->select($subTableSelectionFields);

            $uniqueKeyField = $licenseDef->uniqueKeyField;
            $builder->join($subtable, $model->getTableName() . '.license_number = ' . $subtable . '.' . $uniqueKeyField);
            $data = $model->first();
        } else {
            $data = $model->first();
            $licenseType = $data['type'];
            try {
                $subModel = new LicensesModel();
                $licenseDetails = $subModel->getLicenseDetailsFromSubTable($uuid, $licenseType);
                $data = array_merge($data, $licenseDetails);
            } catch (\Throwable $th) {
                log_message('error', "License with no details {{$data['license_number']} }" . $th->getMessage());
            }
        }



        if (!$data) {
            throw new Exception("License not found");
        }

        return $data;
    }
    /**
     * Generate a secure 6-digit numeric token
     * Uses cryptographically secure random number generation
     * 
     * @return string 6-digit numeric token
     */
    public static function generateSecure6DigitToken(
    ): string {
        // Generate a random number between 100000 and 999999
        $token = random_int(100000, 999999);
        return (string) $token;
    }

    /**
     * Generate a 6-digit token with expiration time
     * Returns both token and expiration timestamp
     * 
     * @param int $expirationMinutes Minutes until token expires (default: 15)
     * @return array ['token' => string, 'expires_at' => int]
     */
    public static function generate6DigitTokenWithExpiration(int $expirationMinutes = 15): array
    {
        $token = self::generateSecure6DigitToken();
        $expiresAt = time() + ($expirationMinutes * 60);

        return [
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Gets the payment options defined in the app settings
     * @return array{purposes: array {}, paymentMethods: array} the payment options
     */
    public static function getPaymentSettings()
    {
        return self::getAppSettings("payments");
    }

    /**
     * Check if data matches criteria
     * 
     * Criteria is an array of rules to match.
     * Each rule is an associative array with two keys: 'field' and 'value'.
     * 
     * 'field' is the key in the data to check
     * 'value' is an array of allowed values. If the first value in the array is 1, then any non-empty value in the data will match.
     * If the first value in the array is 0, then only empty values in the data will match i.e. empty strings or null.
     * If the first value in the array is neither 1 nor 0, then any value in the array will be checked against the value in the data.
     * 
     * @param array $criteria array of rules to match
     * @param array $data data to check
     * @return bool true if all criteria match
     */
    public static function criteriaMatch(array $criteria, array $data)
    {

        foreach ($criteria as $criterion) {
            $field = $criterion['field'] ?? '';
            $values = $criterion['value'] ?? [];
            // Check if the field exists in the $data
            if (!array_key_exists($field, $data)) {
                return false;
            }

            // If no values specified, continue to next criterion
            if (empty($values)) {
                continue;
            }

            $dataValue = $data[$field];
            $firstValue = $values[0];
            // Special case: first value is 1 - match any non-empty value
            if ($firstValue == 1) {
                $isEmpty = ($dataValue === null || (is_string($dataValue) && trim($dataValue) === ''));
                if ($isEmpty) {
                    return false;
                }
                continue;
            }

            // Special case: first value is 0 - match only empty values
            if ($firstValue == 0) {
                $isEmpty = ($dataValue === null || (is_string($dataValue) && trim($dataValue) === ''));
                if (!$isEmpty) {
                    return false;
                }
                continue;
            }

            // Regular case: check if data value is in the allowed values array
            if (!in_array($dataValue, $values)) {
                return false;
            }

        }


        return true;
    }

    /**
     * Get license renewal details
     * 
     * @param string $uuid the uuid of the license renewal
     * @return array the license renewal details
     * @throws \InvalidArgumentException if the license renewal does not exist
     */
    public static function getLicenseRenewalDetails($uuid)
    {
        $model = new LicenseRenewalModel();
        $builder = $model->builder();
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $builder->select($model->getTableName() . '.*');
        $builder->select("JSON_UNQUOTE(data_snapshot) AS data_snapshot");
        $data = $model->first();

        if (!$data) {
            throw new \InvalidArgumentException("License renewal not found");
        }

        $model2 = new LicenseRenewalModel();
        $builder2 = $model2->builder();
        $builder2->where($model2->getTableName() . '.uuid', $uuid);
        $builder2 = $model->addLicenseDetails($builder2, $data['license_type']);

        $fullData = $model2->first();
        $data_snapshot = empty($data['data_snapshot']) ? [] : json_decode($data['data_snapshot'], true);
        unset($fullData['data_snapshot']);
        //for practitioners, there's no name field. use the first name and last name instead
        if (!array_key_exists('name', $fullData) && array_key_exists('first_name', $fullData) && array_key_exists('last_name', $fullData)) {
            $fullData['name'] = $fullData['first_name'] . ' ' . $fullData['last_name'];
        }
        return array_merge($fullData, $data_snapshot);
    }

    /**
     * Get the details for a payment from the relevant table. each payment purpose has a table which contains the details of the uuid. the expected values are in license_renewal, license.
     * 
     * @param string $purpose the payment purpose
     * @param string $uuid the uuid of the license or license renewal
     * @return array the details for the payment
     * @throws ConfigException if the payment purpose is invalid
     * @throws ConfigException if the source table name is not found
     * @throws ConfigException if the source table name is invalid
     */
    public static function getUuidDetailsForPayment(string $purpose, string $uuid)
    {
        /**
         * @var array{defaultInvoiceItems: array {criteria: array {field:string, value:string[]}[], feeServiceCodes: array}[], paymentMethods: array, sourceTableName: string}
         */
        $purposes = self::getPaymentSettings()["purposes"];
        //get the default fees
        if (!isset($purposes[$purpose])) {
            throw new ConfigException("Invalid payment purpose: $purpose");
        }
        $sourceTable = $purposes[$purpose]["sourceTableName"];
        //the purpose could be one of license_renewal, license, application.
        if (empty($sourceTable)) {
            throw new ConfigException("table name not found for purpose: $purpose");
        }
        switch ($sourceTable) {
            case "license_renewal":
                $details = self::getLicenseRenewalDetails($uuid);
                $details['unique_id'] = $details['license_number'];
                return $details;
            case "license":

                $details = self::getLicenseDetails($uuid);
                $details['unique_id'] = $details['license_number'];
                return $details;
            default:
                throw new ConfigException("Invalid source table: $purpose");
        }
    }
}
