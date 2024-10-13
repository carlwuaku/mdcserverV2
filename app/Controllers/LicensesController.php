<?php

namespace App\Controllers;

use App\Helpers\LicenseUtils;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\ActivitiesModel;
use App\Models\Practitioners\PractitionerAdditionalQualificationsModel;
use App\Models\Practitioners\PractitionerRenewalModel;
use App\Models\Practitioners\PractitionerWorkHistoryModel;

use App\Models\Licenses\LicensesModel;
use App\Helpers\Utils;
use \Exception;
use SimpleSoftwareIO\QrCode\Generator;
class LicensesController extends ResourceController
{
    private $licenseUtils;
    public function __construct()
    {
        $this->licenseUtils = new LicenseUtils();
    }

    public function createLicense()
    {
        try {
            $type = $this->request->getPost('type');
            $rules = [
                "license_number" => "required|is_unique[licenses.license_number]",
                "registration_date" => "required|valid_date",
                "email" => "required|valid_email",
                "phone" => "required",
                "type" => "required"
            ];
            try {
                $licenseValidation = Utils::getLicenseOnCreateValidation($type);
                $rules = array_merge($rules, $licenseValidation);
            } catch (\Throwable $th) {
                log_message("error", $th->getMessage());
            }

            if (!$this->validate($rules)) {
                return $this->respond(['message' => $this->validator->getErrors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->request->getPost();
            $model = new LicensesModel($type);
            //get only the last part of the picture path
            // if (array_key_exists("picture", $data) && !empty($data['picture'])) {
            //     $splitPicturePath = explode("/", $data['picture']);
            //     $data['picture'] = array_pop($splitPicturePath);
            // }
            $model->db->transException(true)->transStart();
            $model->insert($data);
            $model->createOrUpdateLicenseDetails($type, $data);
            $model->db->transComplete();

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();

            $activitiesModel->logActivity("Created license {$data['license_number']}");
            //if registered this year, retain the person
            return $this->respond(['message' => 'License created successfully', 'data' => null], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateLicense($uuid)
    {
        try {
            $model = new LicensesModel();
            $oldData = $model->where(["uuid" => $uuid])->first();
            if (!$oldData) {
                throw new Exception("License not found");
            }
            $type = $oldData['type'];
            $rules = [
                "license_number" => "if_exist|is_unique[licenses.license_number,uuid,$uuid]",
                "uuid" => "required",
                "registration_date" => "if_exist|required|valid_date",
            ];
            try {
                $licenseValidation = Utils::getLicenseOnUpdateValidation($type);
                $rules = array_merge($rules, $licenseValidation);
            } catch (\Throwable $th) {
                log_message("error", $th->getMessage());
            }

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->request->getVar();
            $data->uuid = $uuid;

            $data->license_number = $oldData['license_number'];
            if (property_exists($data, "id")) {
                unset($data->id);
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $licenseUpdateData = $model->createArrayFromAllowedFields((array) $data);
            $model->db->transException(true)->transStart();
            $model->builder()->where(['uuid' => $uuid])->update($licenseUpdateData);
            $model->createOrUpdateLicenseDetails($type, $data);
            $model->db->transComplete();

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated license {$oldData['license_number']}. Changes: $changes");

            return $this->respond(['message' => 'License updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deleteLicense($uuid)
    {
        try {
            $model = new LicensesModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted license {$data['license_number']}.");

            return $this->respond(['message' => 'License deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }

    }

    public function countLicenses()
    {
        try {

            $param = $this->request->getVar('param');
            $model = new LicensesModel();
            $filterArray = $model->createArrayFromAllowedFields((array) $this->request->getGet());
            // Validate inputs here

            $builder = $param ? $model->search($param) : $model->builder();
            array_map(function ($value, $key) use ($builder) {
                $builder->where($key, $value);
            }, $filterArray, array_keys($filterArray));

            $total = $builder->countAllResults();
            return $this->respond([
                'data' => $total
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restoreLicense($uuid)
    {
        try {
            $model = new LicensesModel();
            if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $model->where(["uuid" => $uuid])->first();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Restored license {$data['license_number']} from recycle bin");

            return $this->respond(['message' => 'License restored successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }


    }



    public function getLicense($uuid)
    {
        $model = new LicensesModel();
        $data = $this->licenseUtils->getLicenseDetails($uuid);
        if (!$data) {
            return $this->respond("Practitioner not found", ResponseInterface::HTTP_BAD_REQUEST);
        }
        $model->licenseType = $data['type'];//this retrieves the correct display columns
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getLicenses()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new LicensesModel();
            /** if set, use this year for checking whether the license is in goodstanding */
            $renewalDate = $this->request->getVar('renewalDate');
            if ($renewalDate) {
                $model->renewalDate = date("Y-m-d", strtotime($renewalDate));
            }
            $model->joinSearchFields = [
                "facilities" => [
                    "fields" => ["name", "town", "suburb", "business_type"],
                    "joinCondition" => "licenses.license_number = facilities.license_number"
                ]
            ];
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
            log_message("info", $builder->getCompiledSelect(false));
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns(),
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getPractitionerQualifications()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new PractitionerAdditionalQualificationsModel();
            $registration_number = $this->request->getGet('registration_number');
            $builder = $param ? $model->search($param) : $model->builder();
            if ($registration_number !== null) {
                $builder->where(["registration_number" => $registration_number]);
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
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getPractitionerQualification($uuid)
    {
        $model = new PractitionerAdditionalQualificationsModel();
        $builder = $model->builder();
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();
        if (!$data) {
            return $this->respond("Practitioner qualification not found", ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond("Practitioner work history not found", ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
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
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new PractitionerWorkHistoryModel();
            $registration_number = $this->request->getGet('registration_number');
            $builder = $param ? $model->search($param) : $model->builder();
            if ($registration_number !== null) {
                $builder->where(["registration_number" => $registration_number]);
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
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }
    //renewal

    public function getPractitionerRenewals($practitioner_uuid = null)
    {
        try {
            $rules = [
                "start_date" => "if_exist|valid_date",
                "expiry" => "if_exist|valid_date",
            ];
            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new PractitionerRenewalModel();

            $registration_number = $this->request->getGet('registration_number');
            $status = $this->request->getGet('status');
            $start_date = $this->request->getGet('start_date');
            $expiry = $this->request->getGet('expiry');
            $practitioner_type = $this->request->getGet('practitioner_type');
            $created_on = $this->request->getGet('created_on');

            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            if ($registration_number !== null) {
                $builder->where("registration_number", $registration_number);
            }
            if ($practitioner_uuid !== null) {
                $builder->where("practitioner_uuid", $practitioner_uuid);
            }
            if ($status !== null) {
                $builder->where('status', $status);
            }
            if ($start_date !== null) {
                $builder->where('year >=', $start_date);
            }
            if ($expiry !== null) {
                $builder->where('expiry <=', $expiry);
            }
            if ($practitioner_type !== null) {
                $builder->where('practitioner_type', $practitioner_type);
            }
            if ($practitioner_type !== null) {
                $builder->where('practitioner_type', $practitioner_type);
            }
            if ($created_on !== null) {
                $builder->where('date(created_on)  = ', $created_on);
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
                'displayColumns' => $model->getDisplayColumns(),
                'columnLabels' => $model->getDisplayColumnLabels(),
                'columnFilters' => $model->getDisplayColumnFilters()

            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
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
            return $this->respond("Practitioner renewal not found", ResponseInterface::HTTP_BAD_REQUEST);
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
            $startDate = $this->request->getPost('year') ?? date("Y-m-d");
            $year = date('Y', strtotime($startDate));
            $data = $this->request->getPost();
            $model = new PractitionerRenewalModel();
            $expiry = $this->request->getPost('expiry');
            $today = date("Y-m-d");
            //start a transaction
            $model->db->transException(true)->transStart();

            $place_of_work = $this->request->getPost('place_of_work');
            $region = $this->request->getPost('region');
            $district = $this->request->getPost('district');
            $institution_type = $this->request->getPost('institution_type');
            $specialty = $this->request->getPost('specialty');
            $subspecialty = $this->request->getPost('subspecialty');
            $college_membership = $this->request->getPost('college_membership');

            PractitionerUtils::retainPractitioner(
                $practitioner_uuid,
                $expiry,
                $data,
                $year,
                $place_of_work,
                $region,
                $district,
                $institution_type,
                $specialty,
                $subspecialty,
                $college_membership
            );

            $model->db->transComplete();

            return $this->respond(['message' => "Renewal created successfully", 'data' => ""], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);

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
            log_message("info", $practitioner_uuid);
            $practitioner = $this->practitionerUtils->getPractitionerDetails($practitioner_uuid);
            if (empty($expiry)) {
                $data->expiry = PractitionerUtils::generateRenewalExpiryDate($practitioner, $startDate);
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
            } else {
                $data->qr_code = null;
                $data->qr_text = null;
            }

            $model = new PractitionerRenewalModel();
            $model->db->transStart();
            $oldData = $model->where(["uuid" => $uuid])->first();
            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $model->builder()->where(['uuid' => $uuid])->update($data);
            $LicensesModel = new LicensesModel();

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
            $LicensesModel->builder()->where(['uuid' => $practitioner_uuid])->update($practitionerUpdate);
            $model->db->transComplete();
            if ($model->db->transStatus() === false) {
                log_message("error", $model->getError());
                return $this->respond(['message' => "Error updating data. Please make sure all fields are filled correctly and try again"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("updated renewal for practitioner {$data->registration_number}. Changes: $changes");

            return $this->respond(['message' => 'Practitioner renewal updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);

        }
    }

    public function deletePractitionerRenewal($uuid)
    {
        try {
            $model = new PractitionerRenewalModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted renewal for practitioner {$data['registration_number']}. ");

            return $this->respond(['message' => 'Practitioner renewal deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);

        }
    }

    public function countRenewals()
    {
        try {
            $rules = [
                "start_date" => "if_exist|valid_date",
                "expiry" => "if_exist|valid_date",
            ];
            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $param = $this->request->getVar('param');
            $model = new PractitionerRenewalModel();
            $registration_number = $this->request->getGet('registration_number');
            $status = $this->request->getGet('status');
            $start_date = $this->request->getGet('start_date');
            $expiry = $this->request->getGet('expiry');
            $practitioner_type = $this->request->getGet('practitioner_type');

            // Validate inputs here

            $builder = $param ? $model->search($param) : $model->builder();
            if ($registration_number !== null) {
                $builder->where('registration_number', $registration_number);
            }
            if ($status !== null) {
                $builder->where('status', $status);
            }
            if ($start_date !== null) {
                $builder->where('year >=', $start_date);
            }
            if ($expiry !== null) {
                $builder->where('expiry <=', $expiry);
            }
            if ($practitioner_type !== null) {
                $builder->where('practitioner_type', $practitioner_type);
            }
            $total = $builder->countAllResults();
            return $this->respond([
                'data' => $total
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getLicenseFormFields($licenseType)
    {
        $licenseModel = new LicensesModel();
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            //add the general license fields
            $licenseFields = array_merge($licenseModel->getFormFields(), $licenseDef->fields);
            return $this->respond([
                'data' => $licenseFields
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }


}
