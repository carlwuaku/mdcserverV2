<?php
namespace App\Helpers;

use App\Models\Licenses\LicenseRenewalModel;
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
     * @param string $uuid The UUID/license number of the license
     * @return array The license data if found, 
     * @throws Exception If license is not found
     */
    public static function getLicenseDetails(string $uuid): array
    {
        $model = new LicensesModel();
        $builder = $model->builder();
        $builder = $model->addCustomFields($builder);
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $builder->orWhere($model->getTableName() . '.license_number', $uuid);
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

    /**
     * Retain a license.
     *
     * @param string $license_uuid The UUID of the license
     * @param array $data The data to insert
     */
    public static function retainLicense(
        string $license_uuid,
        array $data,
    ) {

        try {
            $model = new LicenseRenewalModel();
            $license = self::getLicenseDetails($license_uuid);
            $licenseType = $license['type'];
            $license_number = $license['license_number'];
            $startDate = $data['start_date'];

            if (empty($startDate)) {
                $startDate = self::generateRenewalStartDate($license);
                $data['start_date'] = $startDate;
            } else {
                $data['start_date'] = date('Y-m-d', strtotime($startDate));
            }
            $expiry = $data['expiry'];
            if (empty($expiry)) {
                $data['expiry'] = self::generateRenewalExpiryDate($license, $startDate);
            } else {
                $data['expiry'] = date('Y-m-d', strtotime($expiry));
            }
            $year = date('Y', strtotime($startDate));
            $code = md5($license['license_number'] . "%%" . $year);
            $qrText = site_url("api/verify/renewal/$code");// "manager.mdcghana.org/api/verifyRelicensure/$code";



            $qrCode = Utils::generateQRCode($qrText, false);
            $data['qr_code'] = $qrCode;
            $data['qr_text'] = $qrText;

            $data['license_type'] = $licenseType;
            $formData = $model->createArrayFromAllowedFields($data, true);
            // log_message('info', print_r($formData, true));
            // log_message('info', $model->builder()->set($formData)->getCompiledInsert());

            $model->set($formData)->insert();
            $id = $model->getInsertID();
            // log_message('info', 'Renewal created successfully');

            $LicensesModel = new LicensesModel();
            $subModel = new LicenseRenewalModel();
            $subModel->createOrUpdateSubDetails($id, $licenseType, $data);
            // log_message('info', 'subRenewal created successfully');
            //a trigger in the database will update the license table with the renewal date, expiry and status
            //get the fields to update based on the renewal type
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $fieldsToUpdate = $licenseDef->fieldsToUpdateOnRenewal;

            $licenseUpdate = [
            ];
            foreach ($fieldsToUpdate as $key => $value) {
                $licenseUpdate[$value] = $data[$value];
            }
            if (!empty($licenseUpdate)) {
                $LicensesModel->builder()->where(['uuid' => $license_uuid])->set($licenseUpdate)->update();
            }

            //send email to the user from here if the setting RENEWAL_EMAIL_TO is set to true
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("added renewal record for $license_number ");




        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            throw new Exception("Error inserting data." . $th->getMessage());
        }

    }

    /**
     * update a license renewal.
     *
     * @param string $renewal_uuid The UUID of the renewal
     * @param array $data The data to update
     */
    public static function updateRenewal(
        string $renewal_uuid,
        array $data,
    ) {

        try {
            $model = new LicenseRenewalModel();
            $renewal = $model->builder()->where('uuid', $renewal_uuid)->get()->getFirstRow('array');
            log_message("info", print_r($renewal, true));

            $licenseType = $renewal['license_type'];
            $license_number = $renewal['license_number'];



            $year = date('Y', strtotime($renewal['start_date']));
            if ($data['status'] === "Approved") {//check for the terminal status from the app settings for that license type
                $code = md5($renewal['license_number'] . "%%" . $year);
                $qrText = "manager.mdcghana.org/api/verifyRelicensure/$code";
                $qrCodeGenerator = new Generator();
                $qrCode = $qrCodeGenerator
                    ->size(200)
                    ->margin(10)
                    ->generate($qrText);
                $data['qr_code'] = $qrCode;
                $data['qr_text'] = $qrText;
            }
            $formData = $model->createArrayFromAllowedFields($data, false);
            // log_message('info', print_r($formData, true));
            log_message('info', print_r($formData, true));

            $model->where("uuid", $renewal_uuid)->set($formData)->update();
            $id = $renewal['id'];
            log_message('info', 'Renewal updated successfully');

            $LicensesModel = new LicensesModel();
            $subModel = new LicenseRenewalModel();
            $subModel->createOrUpdateSubDetails($id, $licenseType, $data);
            // log_message('info', 'subRenewal created successfully');
            //a trigger in the database will update the license table with the renewal date, expiry and status
            //get the fields to update based on the renewal type
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $fieldsToUpdate = $licenseDef->fieldsToUpdateOnRenewal;

            $licenseUpdate = [
            ];
            foreach ($fieldsToUpdate as $key => $value) {
                $licenseUpdate[$value] = $data[$value];
            }
            if (!empty($licenseUpdate)) {
                $LicensesModel->builder()->where(['uuid' => $renewal['license_uuid']])->set($licenseUpdate)->update();
            }

            //send email to the user from here if the setting RENEWAL_EMAIL_TO is set to true
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("updated renewal record for $license_number.  ");




        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            throw new Exception("Error updating data." . $th->getMessage());
        }

    }

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