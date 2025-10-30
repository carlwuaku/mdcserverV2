<?php
namespace App\Helpers;
use Exception;
use App\Models\Housemanship\HousemanshipPostingsModel;
use App\Models\Housemanship\HousemanshipPostingDetailsModel;
use App\Models\Housemanship\HousemanshipFacilitiesModel;
use App\Helpers\Types\HousemanshipPostingDetailsType;
use App\Helpers\Types\HousemanshipPostingType;
use App\Models\ActivitiesModel;
class HousemanshipUtils
{
    /**
     * Create a new posting, along with its details
     *
     * @param HousemanshipPostingType $data
     * @param string $user
     * @return string
     */
    public static function createPosting($data, $user): string
    {
        try {
            $rules = [
                "license_number" => "required|is_not_unique[licenses.license_number]",
                "session" => "required",
                "year" => "required|integer|exact_length[4]",
                "letter_template" => "required|is_not_unique[print_templates.template_name]",
                "tags" => "permit_empty",
                "practitioner_details" => "required",
                "details" => "required"
            ];
            $validation = \Config\Services::validation();

            if (!$validation->setRules($rules)->run((array) $data)) {
                throw new Exception($validation->getErrors());
            }
            $model = new HousemanshipPostingsModel();
            $model->db->transException(true)->transStart();
            $postingId = $model->insert($data);
            $posting = $model->where(['id' => $postingId])->first();
            if (!$posting) {
                throw new Exception("Failed to create posting");
            }
            $postingUuid = $posting['uuid'];
            /**
             * @var HousemanshipPostingDetailsType[] $details
             */
            $details = $data->details;
            $detailsValidationRules = [
                "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
                "discipline" => "permit_empty|is_not_unique[housemanship_disciplines.name]",
                "start_date" => "permit_empty|valid_date",
                "end_date" => "permit_empty|valid_date",
            ];
            foreach ($details as $postingDetail) {
                $postingDetail->posting_uuid = $postingUuid;
                $validation = \Config\Services::validation();

                if (!$validation->setRules($detailsValidationRules)->run((array) $postingDetail)) {
                    $message = implode(" ", array_values($validation->getErrors()));
                    throw new Exception($message);
                }
                //get the facility details
                $facilityModel = new HousemanshipFacilitiesModel();
                $facility = $facilityModel->where(['name' => $postingDetail->facility_name])->first();
                if (!$facility) {
                    throw new Exception("Facility not found");
                }
                $postingDetail->facility_region = $facility['region'];
                $postingDetail->facility_details = json_encode($facility);
                $postingDetailsModel = new HousemanshipPostingDetailsModel();
                $postingDetailsModel->insert($postingDetail);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Added housemanship posting {$data->session} posting for {$data->license_number}", $user, "housemanship");
            $model->db->transComplete();
            return $postingId;
        } catch (Exception $e) {
            log_message('error', 'Failed to create posting: ' . $e);
            throw new Exception("Failed to create posting: " . $e->getMessage());
        }

    }
}


