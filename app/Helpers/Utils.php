<?php

namespace App\Helpers;

use App\Helpers\Types\CriteriaType;
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
use App\Helpers\Types\ApplicationFormTemplateType;
use App\Models\SettingsModel;
use App\Helpers\Types\DataResponseType;
use App\Helpers\Types\AppSettingsLicenseType;
use App\Helpers\Types\HousemanshipApplicationFormTagsType;
use App\Helpers\Types\SystemSettingsType;
use App\Helpers\Types\TrainingInstitutionSettingsType;
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
            throw new Exception("Template file not found", 1);
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
     * Generate a QR code as a PNG image and save it to a file if specified or return a data URI.
     * @param string $qrText The text to be encoded into the QR code.
     * @param bool $saveFile Whether to save the QR code to a file or return a data URI.
     * @param string $filename The file name to save the QR code to. If empty, a unique name will be generated.
     * @return string The path to the saved file or a data URI.
     * @throws \Throwable If something goes wrong.
     */
    public static function generateQRCode(string $qrText, bool $saveFile, string $filename = ""): string
    {
        try {
            $writer = new PngWriter();

            // Create QR code
            $qrCode = new QrCode(
                data: $qrText,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255),

            );

            $logoPath = FCPATH . self::getAppSettings('logo');

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

            $result = $writer->write($qrCode, $logo);

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
        } catch (\Throwable $th) {
            throw $th;
        }

    }


    public static function fillTemplate()
    {
    }

    public static function generateApplicationCode($formType)
    {
        $year = date('y');
        $prefix = strtoupper(substr($formType, 0, 3));
        // Generate a random 6-character suffix using a combination of letters and numbers - year

        $suffix = substr(uniqid(), 5, 6) . "-" . $year;
        return $prefix . "-" . $suffix;
    }

    /**
     * get the value for a key in app settings
     * First checks for database overrides, then falls back to file
     * @param string $key
     * @return array|null|string|bool|int
     */
    public static function getAppSettings(?string $key = null)
    {
        // If no key specified, return entire config with overrides applied
        if ($key === null) {
            return self::getAllAppSettingsWithOverrides();
        }

        // Check cache first
        $cacheKey = 'app_setting_' . $key;
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Check for database override
        $model = new \App\Models\AppSettingsOverridesModel();
        $override = $model->getActiveOverride($key);

        if ($override) {
            // Get file value for merge strategies
            $data = json_decode(file_get_contents(self::getAppSettingsFileName()), true);
            $fileValue = $data[$key] ?? null;

            $overrideValue = self::decodeSettingValue($override['setting_value'], $override['value_type']);
            $mergeStrategy = $override['merge_strategy'] ?? 'replace';

            // Apply merge strategy
            $value = self::applyMergeStrategy($fileValue, $overrideValue, $mergeStrategy, $override['value_type']);

            // Cache for 1 hour
            cache()->save($cacheKey, $value, 3600);
            return $value;
        }

        // Fall back to file
        /**
         * @var array
         */
        $data = json_decode(file_get_contents(self::getAppSettingsFileName()), true);
        $value = $data[$key] ?? null;

        // Cache file value for 1 hour
        if ($value !== null) {
            cache()->save($cacheKey, $value, 3600);
        }

        return $value;
    }

    /**
     * Get all app settings with database overrides applied
     * @return array
     */
    private static function getAllAppSettingsWithOverrides(): array
    {
        $cacheKey = 'app_settings_all_with_overrides';
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Get base settings from file
        $data = json_decode(file_get_contents(self::getAppSettingsFileName()), true);

        // Apply all active overrides
        $model = new \App\Models\AppSettingsOverridesModel();
        $overrides = $model->getAllActiveOverrides();

        foreach ($overrides as $override) {
            $fileValue = $data[$override['setting_key']] ?? null;
            $overrideValue = self::decodeSettingValue(
                $override['setting_value'],
                $override['value_type']
            );
            $mergeStrategy = $override['merge_strategy'] ?? 'replace';

            $data[$override['setting_key']] = self::applyMergeStrategy(
                $fileValue,
                $overrideValue,
                $mergeStrategy,
                $override['value_type']
            );
        }

        // Cache for 1 hour
        cache()->save($cacheKey, $data, 3600);

        return $data;
    }

    /**
     * Decode a setting value based on its type
     * @param string $value
     * @param string $type
     * @return mixed
     */
    private static function decodeSettingValue(string $value, string $type)
    {
        switch ($type) {
            case 'string':
                return $value;
            case 'number':
                return is_numeric($value) ? ($value + 0) : $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
            case 'object':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Apply merge strategy to combine file value with override value
     * @param mixed $fileValue The value from the file
     * @param mixed $overrideValue The override value
     * @param string $strategy The merge strategy (replace, merge, append, prepend)
     * @param string $type The value type
     * @return mixed The merged value
     */
    private static function applyMergeStrategy($fileValue, $overrideValue, string $strategy, string $type)
    {
        // For non-array/object types, always replace
        if ($type !== 'array' && $type !== 'object') {
            return $overrideValue;
        }

        // Handle null file values
        if ($fileValue === null) {
            return $overrideValue;
        }

        switch ($strategy) {
            case 'replace':
                // Complete replacement
                return $overrideValue;

            case 'merge':
                // Merge arrays or objects
                if (is_array($fileValue) && is_array($overrideValue)) {
                    if (self::isAssociativeArray($overrideValue)) {
                        // For associative arrays (objects), merge recursively
                        return array_merge($fileValue, $overrideValue);
                    } else {
                        // For indexed arrays, combine and remove duplicates
                        return array_values(array_unique(array_merge($fileValue, $overrideValue)));
                    }
                }
                return $overrideValue;

            case 'append':
                // Add override items to the end
                if (is_array($fileValue) && is_array($overrideValue)) {
                    if (self::isAssociativeArray($fileValue) && self::isAssociativeArray($overrideValue)) {
                        // For objects, merge
                        return array_merge($fileValue, $overrideValue);
                    } else {
                        // For arrays, append
                        return array_merge($fileValue, $overrideValue);
                    }
                }
                return $overrideValue;

            case 'prepend':
                // Add override items to the beginning
                if (is_array($fileValue) && is_array($overrideValue)) {
                    if (self::isAssociativeArray($fileValue) && self::isAssociativeArray($overrideValue)) {
                        // For objects, override keys take precedence
                        return array_merge($overrideValue, $fileValue);
                    } else {
                        // For arrays, prepend
                        return array_merge($overrideValue, $fileValue);
                    }
                }
                return $overrideValue;

            default:
                return $overrideValue;
        }
    }

    /**
     * Check if an array is associative (object-like) or indexed
     * @param array $array
     * @return bool
     */
    private static function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
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
     * @return AppSettingsLicenseType
     */

    public static function getLicenseSetting(string $license): object
    {
        $licenses = self::getAppSettings("licenseTypes");
        if (!$licenses || !array_key_exists($license, $licenses)) {
            throw new Exception("License not found");
        }
        return AppSettingsLicenseType::fromArray($licenses[$license]);
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
            throw new Exception("$setting->value not found in housemanship settings");
        }
        return $result[$setting->value];
    }



    /**
     * Get the housemanship application form tags based on the given license data.
     *
     * If licenseData is not null, it will filter the tags based on the criteria.
     * If licenseData is null, it will return all the tags.
     *
     * @param array|null $licenseData
     * @return HousemanshipApplicationFormTagsType[]
     * @throws Exception
     */
    public static function getHousemanshipSettingApplicationFormTags(?array $licenseData)
    {
        $result = self::getAppSettings("housemanship");
        if (!$result || !array_key_exists(HousemanshipSetting::APPLICATION_FORM_TAGS->value, $result)) {
            throw new Exception("applicationFormTags not found in housemanship settings");
        }
        $tags = [];
        foreach ($result[HousemanshipSetting::APPLICATION_FORM_TAGS->value] as $tag) {
            $tag = HousemanshipApplicationFormTagsType::fromArray($tag);
            //if licenseData is not null, check if the tag matches the criteria
            if (!empty($licenseData)) {
                if (CriteriaType::matchesCriteria($licenseData, $tag->criteria)) {
                    $tags[] = $tag;
                }
            } else {
                $tags[] = $tag;
            }
        }
        return $tags;
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
                // log_message('info', "Date range: " . $columnName);

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
    public static function addLetterStyling(string $content, string $title)
    {
        /** the letterContainer is an array with an html key that contains the template with some styling and a placeholder [##content##] that will be replaced with the letter content
         * @var string
         */
        $template = self::getTemplateFileContent("letter-container.html");
        $template = str_replace("[##title##]", $title, $template);
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
    public static function getLicenseDetails(string $uuid, ?string $field = null, ?string $type = null): array
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

            try {
                $data = $model->first();
                $licenseType = $data['type'];
                $subModel = new LicensesModel();
                $licenseDetails = $subModel->getLicenseDetailsFromSubTable($uuid, $licenseType);
                $data = array_merge($data, $licenseDetails);
            } catch (\Throwable $th) {
                log_message('error', "License with no details {{$uuid} }" . $th);
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
     * Gets the payment method settings for a given method name
     * @param string $method The payment method name
     * @return array{label: string, type: string, isActive: bool, onStart: string, onComplete: string, description: string, logo: string, paymentBranches: array} The payment method settings
     */
    public static function getPaymentMethodSettings($method)
    {
        return self::getPaymentSettings()["paymentMethods"][$method];
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
        //for practitioners, qualifications is a json array. convert it to an array
        if (array_key_exists('qualifications', $fullData) && !empty($fullData['qualifications'])) {
            $fullData['qualifications'] = json_decode($fullData['qualifications'], true);
        }
        return array_merge($data_snapshot, $fullData);
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

    /**
     * Get the default application form templates
     *
     * @return ApplicationFormTemplateType[] the default application form templates
     */
    public static function getDefaultApplicationFormTemplates()
    {
        $templates = self::getAppSettings(DEFAULT_APPLICATION_FORM_TEMPLATES);
        if (!$templates) {
            throw new Exception("Default application form templates not found");
        }
        return array_map(function ($template) {
            return ApplicationFormTemplateType::fromArray($template);
        }, $templates);
    }


    /**
     * Get the names of the default application form templates
     *
     * @return string[] the names of the default application form templates
     * @throws Exception if default application form templates are not found
     */
    public static function getDefaultApplicationFormTemplatesNames()
    {
        $templates = self::getAppSettings(DEFAULT_APPLICATION_FORM_TEMPLATES);
        if (empty($templates)) {
            throw new Exception("Default application form templates not found");
        }
        return array_map(function ($template) {
            return $template['name'];
        }, $templates);
    }

    /**
     * Get a default application form template by name
     *
     * @param string $name the name of the default application form template
     * @return ApplicationFormTemplateType the default application form template
     * @throws Exception if the default application form templates are not found
     * @throws Exception if the default application form template is not found
     */
    public static function getDefaultApplicationFormTemplate($name)
    {

        $templates = self::getAppSettings(DEFAULT_APPLICATION_FORM_TEMPLATES);
        if ($templates === null) {
            throw new Exception("Default application form templates not found");
        }

        foreach ($templates as $template) {
            if ($template['form_name'] == $name || $template['uuid'] == $name) {
                return ApplicationFormTemplateType::fromArray($template);
            }
        }
        throw new Exception("Default application form template not found");
    }

    public static function generateHashedCacheKey(string $prefix, array $context): string
    {
        // Sort array to ensure consistent ordering
        ksort($context);

        // Create a hash of all context data
        $contextHash = md5(serialize($context));

        return $prefix . '_' . $contextHash;
    }

    /**
     * Retrieves all settings, with optional filtering by a search parameter.
     *
     * @param string|null $param Optional search parameter to filter settings by.
     * @param string $sortBy The field to sort the results by. Defaults to "id".
     * @param string $sortOrder The direction to sort the results by. Defaults to "asc".
     * @param int $per_page The number of results to return per page. Defaults to 100.
     * @param int $page The page number to return. Defaults to 0.
     * @return DataResponseType<\App\Helpers\Types\SettingsType>
     */
    public static function getAllSettings(?string $param = null, string $sortBy = "id", string $sortOrder = "asc", int $per_page = 100, int $page = 0): DataResponseType
    {
        $settingsModel = new SettingsModel();
        $builder = $param ? $settingsModel->search($param) : $settingsModel->builder();
        $builder->orderBy($sortBy, $sortOrder);
        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        /**
         * @var \App\Helpers\Types\SettingsType[] $result
         */
        $result = $builder->get($per_page, $page)->getResult();
        foreach ($result as $value) {
            if ($value->type !== 'string') {
                $value->value = unserialize($value->value);
            }
        }
        /** @var DataResponseType<\App\Helpers\Types\SettingsType>*/
        $response = new DataResponseType($result, $total, $settingsModel->getDisplayColumns(), []);
        return $response;
    }

    /**
     * Retrieves a setting by its name.
     *
     * @param string $name The name of the setting to retrieve.
     * @return mixed The value of the setting, or null if it doesn't exist.
     * If the setting value is a list represented as a ; separated string, it will be returned as an array.
     */
    public static function getSetting($name)
    {
        $settings = service("settings");
        $value = $settings->get($name);
        //legacy settings may be lists represented as ; separated strings
        if (is_string($value) && strpos($value, ';') !== false) {
            $value = explode(';', $value);
        }
        return $value;
    }

    public static function getDefaultPrintTemplates()
    {
        /**
         * @var object{template_name:string, template_content:string}[]
         */
        $templates = self::getAppSettings(DEFAULT_PRINT_TEMPLATES);
        if ($templates === null) {
            throw new Exception("Default print templates not found");
        }

        return $templates;
    }

    public static function getSystemSetting(string $setting)
    {
        /**
         * @var array
         */
        $settings = self::getAppSettings("systemSettings");
        if (!$settings || !array_key_exists($setting, $settings)) {
            throw new Exception("$setting not found in system settings");
        }
        return SystemSettingsType::fromArray($settings[$setting]);
    }

    public static function getTrainingInstitutionsSettings()
    {
        /**
         * @var array
         */
        $settings = self::getAppSettings("trainingInstitutions");

        return TrainingInstitutionSettingsType::fromArray($settings);
    }

}
