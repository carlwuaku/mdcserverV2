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
        log_message('info', print_r($data, true));
        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
    private function getPractitionerDetails(string $uuid): array|object|null
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
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new PractitionerModel();
            /** if set, use this year for checking whether the person is in goodstanding */
            $renewalDate = $this->request->getVar('renewalDate');
            if ($renewalDate) {
                $model->renewalDate = date("Y-m-d", strtotime($renewalDate));
            }
            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            if ($renewalDate) {
                //this is pretty much only used when selecting people for renewal. in this case
                //we want to know the last uuid for a renewal so we can edit or delete it
                $builder = $model->addLastRenewalField($builder);
            }
            $tableName = $model->getTableName();
            $builder->orderBy("$tableName.$sortBy", $sortOrder);

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
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

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
            $builder->orderBy("$sortBy", $sortOrder);

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
        $builder = $model->addCustomFields($builder);
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
            $startDate = $this->request->getPost('year') ?? date("Y-m-d");
            $year = date('Y', strtotime($startDate));
            $data = $this->request->getPost();
            $model = new PractitionerRenewalModel();
            $practitioner = $this->getPractitionerDetails($practitioner_uuid);
            $expiry = $this->request->getPost('expiry');
            if ($practitioner['in_good_standing'] === "yes") {
                return $this->respond(['message' => "Practitioner is already in good standing"], ResponseInterface::HTTP_BAD_REQUEST);
            }



            if (empty($expiry)) {
                $data['expiry'] = Utils::generateRenewalExpiryDate($practitioner, $startDate);
            }

            if ($data['status'] === "Approved") {
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
            //start a transaction
            $model->db->transStart();
            $model->insert($data);
            $place_of_work = $this->request->getPost('place_of_work');
            $region = $this->request->getPost('region');
            $district = $this->request->getPost('district');
            $institution_type = $this->request->getPost('institution_type');
            $specialty = $this->request->getPost('specialty');
            $subspecialty = $this->request->getPost('subspecialty');
            $college_membership = $this->request->getPost('college_membership');

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
        try {
            $rules = [
                "registration_number" => "required",
                "practitioner_uuid" => "required"
            ];
            $practitioner_uuid = $this->request->getVar('practitioner_uuid');

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->request->getVar();
            $data->uuid = $uuid;
            if (property_exists($data, "id")) {
                unset($data->id);
            }

            $registration_number = $this->request->getVar('registration_number');
            $startDate = $this->request->getVar('year') ?? date("Y-m-d");
            $year = date('Y', strtotime($startDate));
            $expiry = $this->request->getVar('expiry');
            $practitioner = $this->getPractitionerDetails($practitioner_uuid);
            if (empty($expiry)) {
                $data->expiry = Utils::generateRenewalExpiryDate($practitioner, $startDate);
            }
            //get only the last part of the picture path
            if (property_exists($data, "picture")) {
                $splitPicturePath = explode("/", $data->picture);
                $data->picture = array_pop($splitPicturePath);
            }

            //if the status is approved, generate a qr code. else remove it if it exists
            if ($data->status === "Approved") {
                    $code = md5($registration_number . "%%" . $year);
                    $qrText = "manager.mdcghana.org/api/verifyRelicensure/$code";
                    $qrCodeGenerator = new Generator;
                    $qrCode = $qrCodeGenerator
                        ->size(200)
                        ->margin(10)
                        ->generate($qrText);
                    $data->qr_code = $qrCode;
                    $data->qr_text = $qrText;
            }
            else{
                $data->qr_code = null;
                $data->qr_text = null;
            }
            
            $model = new PractitionerRenewalModel();
            $model->db->transStart();
            $oldData = $model->where(["uuid" => $uuid])->first();
            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $model->builder()->where(['uuid' => $uuid])->update($data);
            $practitionerModel = new PractitionerModel();

            $place_of_work = $this->request->getVar('place_of_work');
            $region = $this->request->getVar('region');
            $district = $this->request->getVar('district');
            $institution_type = $this->request->getVar('institution_type');
            $specialty = $this->request->getVar('specialty');
            $subspecialty = $this->request->getVar('subspecialty');
            $college_membership = $this->request->getVar('college_membership');

            $practitionerUpdate = [
                "place_of_work" => $place_of_work,
                "region" => $region,
                "district" => $district,
                "institution_type" => $institution_type,
                "specialty" => $specialty,
                "subspecialty" => $subspecialty,
                "college_membership" => $college_membership,
                "last_renewal_start" => $startDate,
                "last_renewal_expiry" => $data->expiry,
                "last_renewal_status" => $data->status,
            ];
            $practitionerModel->builder()->where(['uuid' => $practitioner_uuid])->update($practitionerUpdate);
            $model->db->transComplete();
            if ($model->db->transStatus() === false) {
                log_message("error", $model->getError());
                return $this->respond(['message' => "Error updating data. Please make sure all fields are filled correctly and try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("updated renewal for practitioner {$data->registration_number}. Changes: $changes");

            return $this->respond(['message' => 'Practitioner renewal updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);

        }
    }

    public function deletePractitionerRenewal($uuid)
    {
        try {
            $model = new PractitionerRenewalModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted renewal for practitioner {$data['registration_number']}. ");

            return $this->respond(['message' => 'Practitioner renewal deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);

        }
    }



}
