<?php
namespace App\Helpers;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;

class Utils
{

    // Path to your certificate files - store these securely!
    private static $privateKeyPath = WRITEPATH . 'certs/private.pem';
    private static $publicKeyPath = WRITEPATH . 'certs/public.pem';

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
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),

        );

        // Create generic logo
        // $logo = new Logo(
        //     path: __DIR__ . '/assets/symfony.png',
        //     resizeToWidth: 50,
        //     punchoutBackground: true
        // );

        // Create generic label
        $label = new Label(
            text: 'Label',
            textColor: new Color(255, 0, 0)
        );

        $result = $writer->write(qrCode: $qrCode, label: $label);

        // Validate the result
        $writer->validateResult($result, $qrText);
        // Save it to a file
        $mimetype = "png";
        $file_name = empty($filename) ? uniqid() . ".$mimetype" : $filename . ".$mimetype";

        $writer->validateResult($result, $qrText);
        if ($saveFile) {
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
        $suffix = strtoupper(substr(uniqid(), 0, 6)) . $year;
        return $prefix . $suffix;
    }

    /**
     * get the value for a key in app-settings.json
     * @param string $license
     * @return array
     */
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
     * @return object {table: string, fields: array, onCreateValidation: array, 
     * onUpdateValidation: array, renewalFields: array, renewalTable: string, renewalStages: object, 
     * fieldsToUpdateOnRenewal: array}
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

    public static function generateSecureDocument($documentData)
    {

        // Generate unique document identifier
        $documentId = bin2hex(random_bytes(16));



        // Generate verification token
        $token = self::generateVerificationToken($documentId);
        $verificationUrl = site_url("verify/{$token}");
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

}
