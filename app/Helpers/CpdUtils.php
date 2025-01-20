<?php
namespace App\Helpers;

use App\Models\Cpd\CpdAttendanceModel;
use App\Models\Cpd\CpdModel;
use App\Models\Cpd\ExternalCpdsModel;

class CpdUtils
{
    public static function getCpdScore($licenseNumber)
    {
        $attendanceModel = new CpdAttendanceModel();
        return $attendanceModel->where('license_number', $licenseNumber)->countAllResults();
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
            log_message('info', "year: " . $year);
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

            log_message('info', "CPD Query: " . $builder->getCompiledSelect(false));
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
            log_message('info', "CPD Records: " . json_encode($records));
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
     * Get the total CPD score for a given category for a given year
     * @param string $licenseNumber
     * @param string $year
     * @param string $category
     * @return int
     */
    public function getDpdCategoryScoreByYear($licenseNumber, $year, $category)
    {
        $res = $this->getCPDAttendanceAndScores($licenseNumber, $year);
        $sum = 0;
        foreach ($res['attendance'] as $value) {
            if ((int) $value['category'] === (int) $category) {
                $sum += $value['credits'];
            }
        }
        return $sum;
    }
}