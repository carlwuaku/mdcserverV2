<?php
namespace App\Helpers;

use App\Models\Licenses\LicensesModel;
use App\Models\Practitioners\PractitionerRenewalModel;
use Exception;
use SimpleSoftwareIO\QrCode\Generator;
use App\Models\ActivitiesModel;

class LicenseUtils
{
    // public static function getLicenseName(LicensesModel $license)
    // {
    //     return implode([$license->first_name, $license->middle_name, $license->last_name]);
    // }

    /**
     * Get license details by UUID.
     *
     * @param string $uuid The UUID of the license
     * @return array The license data if found, 
     * @throws Exception If license is not found
     */
    public static function getLicenseDetails(string $uuid): array
    {
        $model = new LicensesModel();
        $builder = $model->builder();
        $builder = $model->addCustomFields($builder);
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();

        if (!$data) {
            throw new Exception("License not found");
        }
        $licenseType = $data['type'];
        try {
            $subModel = new LicensesModel();
            $licenseDetails = $subModel->getLicenseDetailsFromSubTable($uuid, $licenseType);
            $data = array_merge($data, $licenseDetails);
        } catch (\Throwable $th) {
            log_message('error', "License with no details {{$data['license_number']} }" . $th->getMessage());
        }
        return $data;
    }

    // /**
    //  * Retain a license.
    //  *
    //  * @param string $license_uuid The UUID of the license
    //  * @param string $expiry The expiry date of the retention
    //  * @param string $startDate The start date of the retention
    //  * @param array|object $data The data to insert
    //  * @param string $year The year of the retention
    //  * @param string|null $place_of_work The place of work of the license
    //  * @param string|null $region The region of the license
    //  * @param string|null $district The district of the license
    //  * @param string|null $institution_type The institution type of the license
    //  * @param string|null $specialty The specialty of the license
    //  * @param string|null $subspecialty The subspecialty of the license
    //  * @param string|null $college_membership The college membership of the license
    //  */
    // public static function retainLicense(
    //     string $license_uuid,
    //     string|null $expiry,
    //     array|object $data,
    //     string $year,
    //     string|null $place_of_work = null,
    //     string|null $region = null,
    //     string|null $district = null,
    //     string|null $institution_type = null,
    //     string|null $specialty = null,
    //     string|null $subspecialty = null,
    //     string|null $college_membership = null
    // ) {

    //     try {
    //         $model = new PractitionerRenewalModel();
    //         $license = self::getLicenseDetails($license_uuid);
    //         $license_number = $license['license_number'];
    //         if ($license['in_good_standing'] === "yes") {
    //             throw new Exception("License is already in good standing");
    //         }
    //         $startDate = self::generateRenewalStartDate($license);

    //         $data['year'] = $startDate;
    //         if (empty($expiry)) {
    //             $data['expiry'] = self::generateRenewalExpiryDate($license, $startDate);
    //         }
    //         if ($data['status'] === "Approved") {
    //             $code = md5($license['license_number'] . "%%" . $year);
    //             $qrText = "manager.mdcghana.org/api/verifyRelicensure/$code";
    //             $qrCodeGenerator = new Generator;
    //             $qrCode = $qrCodeGenerator
    //                 ->size(200)
    //                 ->margin(10)
    //                 ->generate($qrText);
    //             $data['qr_code'] = $qrCode;
    //             $data['qr_text'] = $qrText;
    //         }
    //         $data['type'] = $license['type'];

    //         $model->insert($data);

    //         $LicensesModel = new LicensesModel();
    //         $licenseUpdate = [
    //             "place_of_work" => $place_of_work,
    //             "region" => $region,
    //             "district" => $district,
    //             "institution_type" => $institution_type,
    //             "specialty" => $specialty,
    //             "subspecialty" => $subspecialty,
    //             "college_membership" => $college_membership,
    //             "last_renewal_start" => $startDate,
    //             "last_renewal_expiry" => $data['expiry'],
    //             "last_renewal_status" => $data['status'],

    //         ];
    //         $LicensesModel->builder()->where(['uuid' => $license_uuid])->update($licenseUpdate);
    //         //send email to the user from here if the setting RENEWAL_EMAIL_TO is set to true
    //         /** @var ActivitiesModel $activitiesModel */
    //         $activitiesModel = new ActivitiesModel();
    //         $activitiesModel->logActivity("added retention record for $license_number ");




    //     } catch (\Throwable $th) {
    //         log_message("error", $th->getMessage());
    //         throw new Exception("Error inserting data." . $th->getMessage());
    //     }

    // }

    /**
     * Generate renewal expiry date based on license and start date.
     *
     * @param array $license The license details
     * @param string $startDate The start date for the renewal
     * @return string The expiry date for the renewal
     */
    public static function generateRenewalExpiryDate(array $license, string $startDate): string
    {
        $year = date('Y', strtotime($startDate));
        //if expiry is empty, and $license->register_type is Permanent, set to the end of the year in $data->year. if $license->register_type is Temporary, set to 3 months from today. if $license->register_type is Provisional, set to a year from the start date in $year
        if ($license['register_type'] === "Temporary") {
            // add 3 months to the date in $startDate
            return date("Y-m-d", strtotime($startDate . " +3 months"));
        } elseif ($license['register_type'] === "Provisional") {
            return date("Y-m-d", strtotime($startDate . " +1 year"));
        } else
            return date("Y-m-d", strtotime($year . "-12-31"));
    }

    /**
     * Generate renewal start date based on license.
     *
     * @param array $license The license details
     * @param string $startDate The start date for the renewal
     * @return string The expiry date for the renewal
     */
    public static function generateRenewalStartDate(array $license): string
    {
        $year = date('Y');
        if ($license['register_type'] === "Temporary") {
            return date("Y-m-d");
        } elseif ($license['register_type'] === "Provisional") {
            return date("Y-m-d");
        } else
            return date("Y-m-d", strtotime("$year-01-01"));
    }

    public static function getLicenseTypeFromLicenseNumber(string $license_number): string
    {
        $prefix = substr($license_number, 0, 3);
        $licenses = Utils::getAppSettings("licenseTypes");
        foreach ($licenses as $key => $value) {
            if ($value->prefix === $prefix) {
                return $key;
            }
        }
        return "";
    }
}