<?php
namespace App\Helpers;

use App\Models\Licenses\LicenseRenewalModel;
use App\Models\Licenses\LicensesModel;
use Exception;
use SimpleSoftwareIO\QrCode\Generator;
use App\Models\ActivitiesModel;

class LicenseUtils extends Utils
{
    // public static function getLicenseName(LicensesModel $license)
    // {
    //     return implode([$license->first_name, $license->middle_name, $license->last_name]);
    // }



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
            $renewalDateGenerator = new LicenseRenewalDateGenerator();
            $dates = $renewalDateGenerator->generateRenewalDates($data);
            $license = self::getLicenseDetails($license_uuid);
            $licenseType = $license['type'];
            $license_number = $license['license_number'];


            $data['start_date'] = $dates['start_date'];
            $data['expiry'] = $dates['expiry_date'];
            $startDate = $data['start_date'];
            $year = date('Y', strtotime($startDate));
            $code = md5($license['license_number'] . "%%" . $year);
            $qrText = site_url("api/verify/renewal/$code");// "manager.mdcghana.org/api/verifyRelicensure/$code";
            //merge the incoming data with the license data
            $fieldsToRemove = ['id', 'uuid', 'created_on', 'modified_on', 'deleted_at'];
            foreach ($fieldsToRemove as $field) {
                if (isset($data[$field])) {
                    unset($data[$field]);
                }
            }
            $data = array_merge($license, $data);


            $qrCode = Utils::generateQRCode($qrText, false);
            $data['qr_code'] = $qrCode;
            $data['qr_text'] = $qrText;

            $data['license_type'] = $licenseType;

            $formData = $model->createArrayFromAllowedFields($data, true);
            $formData['data_snapshot'] = json_encode(self::getLicenseDetails($license_uuid));
            // log_message('info', print_r($formData, true));
            // log_message('info', $model->builder()->set($formData)->getCompiledInsert());

            $model->set($formData)->insert();
            $id = $model->getInsertID();
            // log_message('info', 'Renewal created successfully');

            $LicensesModel = new LicensesModel();
            $subModel = new LicenseRenewalModel();
            $licenseDef = Utils::getLicenseSetting($licenseType);

            $subModel->createOrUpdateSubDetails($id, $licenseType, $data);
            // log_message('info', 'subRenewal created successfully');
            //a trigger in the database will update the license table with the renewal date, expiry and status
            //get the fields to update based on the renewal type

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
            $license = self::getLicenseDetails($renewal['license_number']);
            $licenseType = $renewal['license_type'];
            $license_number = $renewal['license_number'];
            $data['license_type'] = $licenseType;
            $data['license_number'] = $license_number;


            $year = date('Y', strtotime($renewal['start_date']));
            $printableStatuses = array_map(function ($status) {
                return $status['label'];
            }, self::getPrintableRenewalStatuses($licenseType));

            //if the status allows the license to be printed, generate the qr code
            if (empty($renewal['qr_code']) && isset($data['status']) && in_array($data['status'], $printableStatuses)) {
                //check for the terminal status from the app settings for that license type
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
            //unset the $license status as when no status is provided, the default status from the license is used
            unset($license['status']);
            $data = array_merge($license, $data);
            $formData = $model->createArrayFromAllowedFields($data, false);
            //if the online_print_template is an empty string, set it to null
            if (array_key_exists('online_print_template', $data) && $data['online_print_template'] === "") {
                $formData['online_print_template'] = null;
            }
            //the createArrayFromAllowedFields function will remove any fields that are set to null. this is to avoid accidentally setting a field to null when the user does not want to update it or removing sensitive information.
            //there are some cases where some fields are safe to be set to null, so we need to allow them to be set to null.
            $nullableFields = [
                'online_print_template',
                'qr_code',
                'qr_text',
                'approve_online_certificate',
                'online_certificate_start_date',
                'online_certificate_end_date',
                'payment_date',
                'payment_file',
                'payment_file_date',
                'payment_invoice_number'
            ];
            // for each of these ones, if it was set in $data and was null, set it to null
            foreach ($nullableFields as $field) {
                if (array_key_exists($field, $data) && $data[$field] === null) {
                    $formData[$field] = null;
                }
            }

            $model->where("uuid", $renewal_uuid)->set($formData)->update();
            $id = $renewal['id'];

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

    /**
     * check from the renewal table if a license has a record where the date is within the start and expiry dates and the status is approved
     * @param mixed $licenseNumber
     * @param mixed $date
     * @return bool
     */
    public static function licenseIsInGoodStanding($licenseNumber, $date)
    {
        $renewalModel = new LicenseRenewalModel();
        $builder = $renewalModel->builder();
        $builder->where('license_number', $licenseNumber);
        $builder->where('start_date <=', $date);
        $builder->where('expiry >=', $date);
        $builder->where('status', 'Approved');
        $result = $builder->get()->getResult('array');
        return count($result) > 0;
    }

    /**
     * return true if the license requires revalidation. Revalidation means that the data on the license is correct and up to date. how it's implemented is up to the officer
     * @param array $licenseDetails
     * @param string $revalidationPeriod the period after which the license requires revalidation
     * @param string $revalidationMessage a message to return if the license requires revalidation
     * @param string $revalidationManualMessage a message to return if the license was manually marked for revalidation
     * @return array{result: bool, message: string}
     */
    public static function licenseRequiresRevalidation($licenseDetails, $revalidationPeriod = null, $revalidationMessage = "License has lapsed revalidation period", $revalidationManualMessage = "License marked for revalidation")
    {
        $templateObject = new TemplateEngineHelper();
        if (array_key_exists('requires_revalidation', $licenseDetails) && $licenseDetails['requires_revalidation'] == 'yes') {

            return ['result' => true, 'message' => $templateObject->process($revalidationManualMessage, $licenseDetails)];
        }
        if (empty($revalidationPeriod) || !is_numeric($revalidationPeriod) || intval($revalidationPeriod) < 1) {
            return ['result' => false, 'message' => "Revalidation period not set"];
        }
        $last_revalidation = !array_key_exists('last_revalidation_date', $licenseDetails) || $licenseDetails['last_revalidation_date'] == null ? $licenseDetails['created_on'] : $licenseDetails['last_revalidation_date'];
        $revalidationPeriod = intval($revalidationPeriod);
        $diff = self::getDaysDifference($last_revalidation);
        if ($diff > $revalidationPeriod) {
            return ["result" => true, "message" => $templateObject->process($revalidationMessage, array_merge(["days" => $diff], (array) $licenseDetails))];
        } else {
            return ["result" => false, "message" => "Practitioner does not require revalidation"];

        }

    }

    /**
     * Return a list of statuses that can be printed on the renewal certificate
     * @param mixed $licenseType
     * @return array
     */
    public static function getPrintableRenewalStatuses($licenseType)
    {
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $results = [];
        $renewalStages = (array) $licenseDef->renewalStages;
        foreach (array_values($renewalStages) as $value) {
            if ($value['printable']) {
                $results[] = $value;
            }

        }
        return $results;
    }

    public static function getLicenseRenewalStages($licenseType)
    {
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $renewalStages = (array) $licenseDef->renewalStages;

        return array_keys($renewalStages);
    }

    /**
     * checks if a license is eligible for relicensure. this is used when a license holder wants to renew their license from the portal
     * @param mixed $reg_num
     * @param array{restrict:bool, year:string, cpdTotalCutoff:int, cpdCategoriesCutoffs:int, register:string, revalidationPeriod:int, revalidationMessage:string, revalidationManualMessage: string, permitRetention:bool  } $options
     * @throws \Exception
     * @return array{message: string, result: bool, score: int}
     */
    public static function is_eligible_relicensure(
        $reg_num,
        $options = [
            "restrict" => true,
            "year" => "",
            "cpdTotalCutoff" => 0,
            "category1Cutoff" => 0,
            "category2Cutoff" => 0,
            "category3Cutoff" => 0,
            "register" => "",
            "revalidationPeriod" => 0,
            "revalidationMessage" => "",
            "revalidationManualMessage" => "",
            "permitRetention" => false
        ]
    ) {
        $licenseDetails = [];
        try {
            $licenseDetails = self::getLicenseDetails($reg_num);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
        if ($licenseDetails['status'] == 0) {
            throw new Exception("Practitioner is inactive");
        }
        $requires_revalidation = self::licenseRequiresRevalidation($licenseDetails, $options["revalidationPeriod"], $options['revalidationMessage'], $options['revalidationManualMessage']);
        if ($requires_revalidation['result']) {
            return array(
                "result" => false,
                "score" => 0,
                "message" => $requires_revalidation['message']
            );
        }

        $year = empty($options['year']) ? date("Y") : intval($options['year']);

        //IF the restrict settings are set to true, then the person needs to be in good standing at the moment
        if ($options['restrict']) {
            $isInGoodStanding = self::licenseIsInGoodStanding($reg_num, date("Y-m-d"));

            if (!$isInGoodStanding) {
                //not in good standing
                return array(
                    "result" => false,
                    "score" => 0,
                    "message" => "Practitioners must be in good standing to use the online portal"
                );
            }
        }

        $cpd = self::getCPDAttendanceAndScores($reg_num, $year);

        //if the person was granted permission, ignore the cpd
        if ($options['permitRetention']) {
            return array(
                "result" => "1",
                "score" => $cpd['score'],
                "message" => "Practitioner has been granted exception to proceed with relicensure despite CPD requirement."
            );
        }


        //if the person did not meet the minimum requirement, just return false
        if ($cpd['score'] < $options['cpdTotalCutoff'] && $options['register'] == 'Permanent') {
            return array(
                "result" => "-1",
                "score" => $cpd['score'],
                "message" => "You did not meet the minimum CPD requirement. You obtained {$cpd['score']} credit points. The minimum required is {$options['cpdTotalCutoff']} credit points"
            );
        }

        $attendance = $cpd['attendance'];
        $person_cat_1_score = $person_cat_2_score = $person_cat_3_score = 0;
        foreach ($attendance as $value) {
            if ((int) $value['category'] === 1) {
                $person_cat_1_score += $value['credits'];
            } else if ((int) $value['category'] === 2) {
                $person_cat_2_score += $value['credits'];
            } else if ((int) $value['category'] === 3) {
                $person_cat_3_score += $value['credits'];
            }
        }
        //else check if they met the requirments for the categories
        if ($person_cat_1_score < $options['category1Cutoff']) {
            return array(
                "result" => false,
                "score" => $cpd['score'],
                "message" => "Obtained minimum total, but did not meet minimum requirement for cpd category 1."
                    . " Had $person_cat_1_score, minimum is {$options['category1Cutoff']}"
            );
        }
        if ($person_cat_2_score < $options['category2Cutoff']) {
            return array(
                "result" => false,
                "score" => $cpd['score'],
                "message" => "Obtained minimum total, but did not meet minimum requirement for cpd category 2."
                    . " Had $person_cat_2_score, minimum is {$options['category2Cutoff']}"
            );
        }
        if ($person_cat_3_score < $options['category3Cutoff']) {
            return array(
                "result" => false,
                "score" => $cpd['score'],
                "message" => "Obtained minimum total, but did not meet minimum requirement for cpd category 3."
                    . " Had $person_cat_3_score, minimum is {$options['category3Cutoff']}"
            );
        }
        return array(
            "result" => true,
            "score" => $cpd['score'],
            "message" => "Meets all CPD requirements"
        );
    }
}