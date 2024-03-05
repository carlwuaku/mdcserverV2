<?php

namespace App\Controllers;

use App\Models\ActivitiesModel;
use App\Models\PractitionerAdditionalQualificationsModel;
use App\Models\PractitionerRenewalModel;
use App\Models\PractitionerWorkHistoryModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PractitionerModel;
use App\Helpers\Utils;
use \Exception;
use SimpleSoftwareIO\QrCode\Generator;

class PractitionerController extends ResourceController
{

    public function createPractitioner()
    {
        $rules = [
            "registration_number" => "required|is_unique[practitioners.registration_number]",
            "last_name" => "required",
            "date_of_birth" => "required|valid_date",
            "sex" => "required",

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new PractitionerModel();
        //get only the last part of the picture path
        if (property_exists($data, "picture")) {
            $splitPicturePath = explode("/", $data->picture);
            $data->picture = array_pop($splitPicturePath);
        }
        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $id = $model->getInsertID();
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();

        $activitiesModel->logActivity("Created practitioner {$data['registration_number']}");
        //if registered this year, retain the person
        return $this->respond(['message' => 'Practitioner created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    }

    public function updatePractitioner($uuid)
    {
        $rules = [
            "registration_number" => "if_exist|is_unique[practitioners.registration_number,uuid,$uuid]",
            "uuid" => "required",
            "date_of_birth" => "if_exist|required|valid_date",

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        if (property_exists($data, "id")) {
            unset($data->id);
        }
        //get only the last part of the picture path
        if (property_exists($data, "picture")) {
            $splitPicturePath = explode("/", $data->picture);
            $data->picture = array_pop($splitPicturePath);
        }
        $model = new PractitionerModel();
        $oldData = $model->where(["uuid" => $uuid])->first();
        $changes = implode(", ", Utils::compareObjects($oldData, $data));
        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Updated practitioner {$oldData['registration_number']}. Changes: $changes");

        return $this->respond(['message' => 'Practitioner updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deletePractitioner($uuid)
    {
        $model = new PractitionerModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$model->where('uuid', $uuid)->delete()) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Deleted practitioner {$data['registration_number']}.");

        return $this->respond(['message' => 'Practitioner deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restorePractitioner($uuid)
    {
        $model = new PractitionerModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $data = $model->where(["uuid" => $uuid])->first();
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Restored practitioner {$data['registration_number']} from recycle bin");

        return $this->respond(['message' => 'Practitioner restored successfully'], ResponseInterface::HTTP_OK);
    }

    /**
     * Get practitioner details by UUID.
     *
     * @param string $uuid The UUID of the practitioner
     * @return PractitionerModel|null The practitioner data if found, null otherwise
     * @throws Exception If practitioner is not found
     */
    private function getPractitionerDetails(string $uuid): array|object|null {
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

    public function getPractitioner($uuid)
    {
        $model = new PractitionerModel();
        $data = $this->getPractitionerDetails($uuid);
        if (!$data) {
            return $this->respond("Practitioner not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getPractitioners()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $model = new PractitionerModel();

            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);

            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPractitionerQualifications()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');

            $model = new PractitionerAdditionalQualificationsModel();
            $registration_number = $this->request->getGet('registration_number');
            $builder = $param ? $model->search($param) : $model->builder();
            if ($registration_number !== null) {
                $builder->where(["registration_number" => $registration_number]);
            }
            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPractitionerQualification($uuid)
    {
        $model = new PractitionerAdditionalQualificationsModel();
        $builder = $model->builder();
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();
        if (!$data) {
            return $this->respond("Practitioner qualification not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function createPractitionerQualification()
    {
        $rules = [
            "registration_number" => "required",
            "institution" => "required",
            "qualification" => "required"

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new PractitionerAdditionalQualificationsModel();

        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $id = $model->getInsertID();

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Added additional qualification {$data['qualification']} to practitioner {$data['registration_number']}");

        //if registered this year, retain the person
        return $this->respond(['message' => 'Additional qualification created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    }

    public function updatePractitionerQualification($uuid)
    {
        $rules = [
            "registration_number" => "required",
            "institution" => "required",
            "qualification" => "required"

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        if (property_exists($data, "id")) {
            unset($data->id);
        }
        //get only the last part of the picture path
        if (property_exists($data, "picture")) {
            $splitPicturePath = explode("/", $data->picture);
            $data->picture = array_pop($splitPicturePath);
        }
        $model = new PractitionerAdditionalQualificationsModel();

        $oldData = $model->where(["uuid" => $uuid])->first();
        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("updated additional qualification for practitioner {$data['registration_number']}. Changes: $changes");

        return $this->respond(['message' => 'Practitioner additional qualification updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deletePractitionerQualification($uuid)
    {
        $model = new PractitionerAdditionalQualificationsModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$model->where('uuid', $uuid)->delete()) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Deleted additional qualification {$data['qualification']} for practitioner {$data['registration_number']}. ");

        return $this->respond(['message' => 'Practitioner additional qualification deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restorePractitionerQualification($uuid)
    {
        $model = new PractitionerAdditionalQualificationsModel();

        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $data = $model->where(["uuid" => $uuid])->first();

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Restored additional qualification {$data['qualification']} for practitioner {$data['registration_number']}. ");

        return $this->respond(['message' => 'Practitioner additional qualification restored successfully'], ResponseInterface::HTTP_OK);
    }


    public function createPractitionerWorkHistory()
    {
        $rules = [
            "registration_number" => "required",
            "institution" => "required",
            "position" => "required",
            "institution_type" => "required"

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new PractitionerWorkHistoryModel();

        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $id = $model->getInsertID();

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Added work history position {$data['position']} at {$data['institution']} to practitioner {$data['registration_number']}");

        return $this->respond(['message' => 'Work history qualification created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    }

    public function getPractitionerWorkHistory($uuid)
    {
        $model = new PractitionerWorkHistoryModel();
        $builder = $model->builder();
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();
        if (!$data) {
            return $this->respond("Practitioner work history not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }



    public function updatePractitionerWorkHistory($uuid)
    {
        $rules = [
            "registration_number" => "required",
            "institution" => "required",
            "position" => "required",
            "institution_type" => "required"

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        //the uuid is set as the id, so we have to remove it so that it doesn't get updated
        if (property_exists($data, "id")) {
            unset($data->id);
        }
        $model = new PractitionerWorkHistoryModel();

        $oldData = $model->where(["uuid" => $uuid])->first();
        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("updated work history for practitioner {$data->registration_number}. Changes: $changes");

        return $this->respond(['message' => 'Practitioner additional qualification updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deletePractitionerWorkHistory($uuid)
    {
        $model = new PractitionerWorkHistoryModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$model->where('uuid', $uuid)->delete()) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Deleted work history {$data['position']}  {$data['institution']} for practitioner {$data['registration_number']}. ");

        return $this->respond(['message' => 'Practitioner work history deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restorePractitionerWorkHistory($uuid)
    {
        $model = new PractitionerWorkHistoryModel();

        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $data = $model->where(["uuid" => $uuid])->first();

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Restored work history {$data['position']} {$data['institution']} for practitioner {$data['registration_number']}. ");

        return $this->respond(['message' => 'Practitioner additional qualification restored successfully'], ResponseInterface::HTTP_OK);
    }

    public function getPractitionerWorkHistories()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');

            $model = new PractitionerWorkHistoryModel();
            $registration_number = $this->request->getGet('registration_number');
            $builder = $param ? $model->search($param) : $model->builder();
            if ($registration_number !== null) {
                $builder->where(["registration_number" => $registration_number]);
            }
            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    //renewal

    public function getPractitionerRenewals($practitioner_uuid = null)
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');

            $model = new PractitionerRenewalModel();
            $registration_number = $this->request->getGet('registration_number');
            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            if ($registration_number !== null) {
                $builder->where(["registration_number" => $registration_number]);
            }
            if ($practitioner_uuid !== null) {
                $builder->where(["practitioner_uuid" => $practitioner_uuid]);
            }
            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPractitionerRenewal($uuid)
    {
        $model = new PractitionerRenewalModel();
        $builder = $model->builder();
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();
        if (!$data) {
            return $this->respond("Practitioner renewal not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function createPractitionerRenewal()
    {
        try {        
        $rules = [
            "registration_number" => "required",
            "year" => "required",
            "practitioner_uuid" => "required",
            "status" => "required",
        ];


        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $practitioner_uuid = $this->request->getPost('practitioner_uuid');

        $registration_number = $this->request->getPost('registration_number');
        $year = $this->request->getPost('year') ?? date("Y");

        $data = $this->request->getPost();
        $model = new PractitionerRenewalModel();
        $practitioner = $this->getPractitionerDetails($practitioner_uuid);

        if ($practitioner['in_good_standing'] === "yes") {
            return $this->respond(['message' => "Practitioner is already in good standing"], ResponseInterface::HTTP_BAD_REQUEST);
        }

        
        
        //if expiry is empty, and $practitioner->register_type is Permanent, set to the end of the year in $data->year. if $practitioner->register_type is Temporary, set to 3 months from today. if $practitioner->register_type is Provisional, set to a year from today
        if ($practitioner['register_type'] === "Permanent") {
            $data['expiry'] = date("Y-m-d", strtotime($year . "-12-31"));
        }
        if ($practitioner['register_type'] === "Temporary") {
            $data['expiry'] = date("Y-m-d", strtotime("+3 months"));
        }
        if ($practitioner['register_type'] === "Provisional") {
            $data['expiry'] = date("Y-m-d", strtotime("+1 year"));
        }
        if($data['status'] === "Approved"){
        $code = md5($registration_number . "%%" . $year);
        $qrText = "manager.mdcghana.org/api/verifyRelicensure/$code";
        $qrCodeGenerator = new Generator;
        $qrCode = $qrCodeGenerator
            ->size(200)
            ->margin(10)
            ->generate($qrText);
        $data['qr_code'] = $qrCode;
        $data['qr_text'] = $qrText;
        }
        $data['year'] = date("Y-m-d", strtotime("$year-01-01"));
        // $data['status'] = "Approved";
        //start a transaction
        $model->db->transStart();
        $model->insert($data);
        $practitionerModel = new PractitionerModel();
        $practitionerUpdate = [
            "place_of_work" => $data['place_of_work'],
            "region" => $data['region'],
            "district" => $data['district'],
            "institution_type" => $data['institution_type'],
            "specialty" => $data['specialty'],
            "subspecialty" => $data['subspecialty'],
            "college_membership" => $data['college_membership']
        ];
        $practitionerModel->builder()->where(['uuid' => $practitioner_uuid])->update($practitionerUpdate);
        $model->db->transComplete();
        if ($model->db->transStatus() === false) {
            log_message("error", $model->getError());
            return $this->respond(['message' => "Error inserting data. Please make sure all fields are filled correctly and try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            // generate an error... or use the log_message() function to log your error
        }
        //send email to the user from here if the setting RENEWAL_EMAIL_TO is set to true
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("added retention record for $registration_number ");
        return $this->respond(['message' => "Renewal created successfully", 'data' => ""], ResponseInterface::HTTP_OK);
    } catch (\Throwable $th) {
        log_message("error", $th->getMessage());
        return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    
    }
    }

    public function updatePractitionerRenewal($uuid)
    {
        $rules = [
            "registration_number" => "required",
            "practitioner_uuid" => "required"
        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        if (property_exists($data, "id")) {
            unset($data->id);
        }
        //get only the last part of the picture path
        if (property_exists($data, "picture")) {
            $splitPicturePath = explode("/", $data->picture);
            $data->picture = array_pop($splitPicturePath);
        }
        $model = new PractitionerRenewalModel();

        $oldData = $model->where(["uuid" => $uuid])->first();
        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("updated renewal for practitioner {$data['registration_number']}. Changes: $changes");

        return $this->respond(['message' => 'Practitioner additional qualification updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deletePractitionerRenewal($uuid)
    {
        $model = new PractitionerRenewalModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$model->where('uuid', $uuid)->delete()) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Deleted renewal for practitioner {$data['registration_number']}. ");

        return $this->respond(['message' => 'Practitioner renewal deleted successfully'], ResponseInterface::HTTP_OK);
    }

    

}
