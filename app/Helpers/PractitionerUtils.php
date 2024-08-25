<?php
namespace App\Helpers;

use App\Models\Practitioners\PractitionerModel;
use App\Models\Practitioners\PractitionerRenewalModel;
use Exception;
use SimpleSoftwareIO\QrCode\Generator;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\ActivitiesModel;

class PractitionerUtils
{
    public static function getPractitionerName(PractitionerModel $practitioner)
    {
        return implode([$practitioner->first_name, $practitioner->middle_name, $practitioner->last_name]);
    }

    /**
     * Get practitioner details by UUID.
     *
     * @param string $uuid The UUID of the practitioner
     * @return PractitionerModel|null The practitioner data if found, null otherwise
     * @throws Exception If practitioner is not found
     */
    public static function getPractitionerDetails(string $uuid): array|object|null
    {
        $model = new PractitionerModel();
        $builder = $model->builder();
        $builder = $model->addCustomFields($builder);
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();
        if (!$data) {
            throw new Exception("Practitioner not found");
        }
        return $data;
    }

    /**
     * Retain a practitioner.
     *
     * @param string $practitioner_uuid The UUID of the practitioner
     * @param string $expiry The expiry date of the retention
     * @param string $startDate The start date of the retention
     * @param array|object $data The data to insert
     * @param string $year The year of the retention
     * @param string|null $place_of_work The place of work of the practitioner
     * @param string|null $region The region of the practitioner
     * @param string|null $district The district of the practitioner
     * @param string|null $institution_type The institution type of the practitioner
     * @param string|null $specialty The specialty of the practitioner
     * @param string|null $subspecialty The subspecialty of the practitioner
     * @param string|null $college_membership The college membership of the practitioner
     */
    public static function retainPractitioner(
        string $practitioner_uuid,
        string|null $expiry,
        array|object $data,
        string $year,
        string|null $place_of_work = null,
        string|null $region = null,
        string|null $district = null,
        string|null $institution_type = null,
        string|null $specialty = null,
        string|null $subspecialty = null,
        string|null $college_membership = null
    ) {

        try {
            $model = new PractitionerRenewalModel();
            $practitioner = self::getPractitionerDetails($practitioner_uuid);
            $registration_number = $practitioner['registration_number'];
            if ($practitioner['in_good_standing'] === "yes") {
                throw new Exception("Practitioner is already in good standing");
            }
                $startDate = self::generateRenewalStartDate($practitioner);
            
            $data['year'] = $startDate;
            if (empty($expiry)) {
                $data['expiry'] = self::generateRenewalExpiryDate($practitioner, $startDate);
            }
            if ($data['status'] === "Approved") {
                $code = md5($practitioner['registration_number'] . "%%" . $year);
                $qrText = "manager.mdcghana.org/api/verifyRelicensure/$code";
                $qrCodeGenerator = new Generator;
                $qrCode = $qrCodeGenerator
                    ->size(200)
                    ->margin(10)
                    ->generate($qrText);
                $data['qr_code'] = $qrCode;
                $data['qr_text'] = $qrText;
            }
            $data['practitioner_type'] = $practitioner['practitioner_type'];
           
            $model->insert($data);

            $practitionerModel = new PractitionerModel();
            $practitionerUpdate = [
                "place_of_work" => $place_of_work,
                "region" => $region,
                "district" => $district,
                "institution_type" => $institution_type,
                "specialty" => $specialty,
                "subspecialty" => $subspecialty,
                "college_membership" => $college_membership,
                "last_renewal_start" => $startDate,
                "last_renewal_expiry" => $data['expiry'],
                "last_renewal_status" => $data['status'],

            ];
            $practitionerModel->builder()->where(['uuid' => $practitioner_uuid])->update($practitionerUpdate);
            //send email to the user from here if the setting RENEWAL_EMAIL_TO is set to true
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("added retention record for $registration_number ");




        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            throw new Exception("Error inserting data.".$th->getMessage());
        }

    }

    /**
     * Generate renewal expiry date based on practitioner and start date.
     *
     * @param array $practitioner The practitioner details
     * @param string $startDate The start date for the renewal
     * @return string The expiry date for the renewal
     */
    public static function generateRenewalExpiryDate(array $practitioner, string $startDate): string
    {
        $year = date('Y', strtotime($startDate));
        //if expiry is empty, and $practitioner->register_type is Permanent, set to the end of the year in $data->year. if $practitioner->register_type is Temporary, set to 3 months from today. if $practitioner->register_type is Provisional, set to a year from the start date in $year
        if ($practitioner['register_type'] === "Temporary") {
            // add 3 months to the date in $startDate
            return date("Y-m-d", strtotime($startDate . " +3 months"));
        } elseif ($practitioner['register_type'] === "Provisional") {
            return date("Y-m-d", strtotime($startDate . " +1 year"));
        } else
            return date("Y-m-d", strtotime($year . "-12-31"));
    }

     /**
     * Generate renewal start date based on practitioner.
     *
     * @param array $practitioner The practitioner details
     * @param string $startDate The start date for the renewal
     * @return string The expiry date for the renewal
     */
    public static function generateRenewalStartDate(array $practitioner): string
    {
        $year = date('Y');
        if ($practitioner['register_type'] === "Temporary") {
            return date("Y-m-d");
        } elseif ($practitioner['register_type'] === "Provisional") {
            return date("Y-m-d");
        } else
            return date("Y-m-d", strtotime("$year-01-01"));
    }
}