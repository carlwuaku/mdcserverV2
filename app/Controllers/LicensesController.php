<?php

namespace App\Controllers;

use App\Helpers\LicenseUtils;
use App\Models\Licenses\LicenseRenewalModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\ActivitiesModel;

use App\Models\Licenses\LicensesModel;
use App\Helpers\Utils;
use \Exception;
use App\Helpers\CacheHelper;

/**
 * @OA\Info(title="API Name", version="1.0")
 * @OA\Tag(name="Tag Name", description="Tag description")
 * @OA\Tag(
 *     name="Licenses",
 *     description="Operations for managing and viewing licenses"
 * )
 */
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
            $licenseType = $this->request->getVar('licenseType') ?? null;
            $licenseJoinConditions = '';
            $filterArray = $model->createArrayFromAllowedFields((array) $this->request->getGet());
            //get the params   that have 'child_' appears .
            /**
             * @var array
             */
            $childParams = array_filter($this->request->getGet(), function ($key) {
                return strpos($key, 'child_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            // if childParams is not empty, 
            if (!empty($childParams)) {
                if (!$licenseType) {
                    throw new Exception("License type is required");
                }
                $licenseDef = Utils::getLicenseSetting($licenseType);
                $renewalSubTable = $licenseDef->renewalTable;
                $licenseTypeTable = $licenseDef->table;
                /**
                 * @var array
                 */
                $joinConditions = [];
                foreach ($childParams as $key => $value) {
                    $joinConditions[] = str_replace('child_', '', $licenseTypeTable . '.' . $key) . ' = ' . "'$value'";
                }
                $licenseJoinConditions = implode(" AND ", $joinConditions);
            }
            $builder = $param ? $model->search($param) : $model->builder();
            array_map(function ($value, $key) use ($builder) {
                if (strpos($key, 'child_') === 0) {
                    $builder->where($key, $value);
                }
            }, $filterArray, array_keys($filterArray));
            if ($licenseType) {
                $builder = $model->addLicenseDetails($builder, $licenseType, true, $licenseJoinConditions);
            }
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
        $model->licenseType = $data['type']; //this retrieves the correct display columns
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
            $licenseType = $this->request->getVar('licenseType') ?? null;
            $licenseJoinConditions = '';
            $model = new LicensesModel();
            /** if set, use this year for checking whether the license is in goodstanding */
            $renewalDate = $this->request->getVar('renewalDate');
            if ($renewalDate) {
                $model->renewalDate = date("Y-m-d", strtotime($renewalDate));
            }
            if ($licenseType) {
                $model->licenseType = $licenseType;
                try {
                    $searchFields = Utils::getLicenseSearchFields($licenseType);
                    $searchFields['table'] = Utils::getLicenseTable($licenseType);
                    $model->joinSearchFields = $searchFields;

                    //get the params   that have 'child_' appears . in the url are converted to _
                    /**
                     * @var array
                     */
                    $childParams = array_filter($this->request->getGet(), function ($key) {
                        return strpos($key, 'child_') === 0;
                    }, ARRAY_FILTER_USE_KEY);
                    // if childParams is not empty, 
                    if (!empty($childParams)) {
                        $licenseDef = Utils::getLicenseSetting($licenseType);
                        $licenseTypeTable = $licenseDef->table;
                        /**
                         * @var array
                         */
                        $joinConditions = [];
                        foreach ($childParams as $key => $value) {
                            $joinConditions[] = str_replace('child_', '', $licenseTypeTable . '.' . $key) . ' = ' . "'$value'";
                        }
                        $licenseJoinConditions = implode(" AND ", $joinConditions);
                    }
                } catch (\Throwable $th) {
                    log_message("error", $th->getMessage());
                }
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
            if ($licenseType) {
                $builder->where("$tableName.type", $licenseType);
                $addJoin = $param || $licenseJoinConditions ? false : true; //if there are child params, the join is already added
                $builder = $model->addLicenseDetails($builder, $licenseType, $addJoin, $licenseJoinConditions);
            }

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



    // public function getPractitionerQualifications()
    // {
    //     try {
    //         $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
    //         $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
    //         $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
    //         $param = $this->request->getVar('param');
    //         $sortBy = $this->request->getVar('sortBy') ?? "id";
    //         $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

    //         $model = new PractitionerAdditionalQualificationsModel();
    //         $registration_number = $this->request->getGet('registration_number');
    //         $builder = $param ? $model->search($param) : $model->builder();
    //         if ($registration_number !== null) {
    //             $builder->where(["registration_number" => $registration_number]);
    //         }
    //         if ($withDeleted) {
    //             $model->withDeleted();
    //         }
    //         $builder->orderBy("$sortBy", $sortOrder);
    //         $totalBuilder = clone $builder;
    //         $total = $totalBuilder->countAllResults();
    //         $result = $builder->get($per_page, $page)->getResult();
    //         return $this->respond([
    //             'data' => $result,
    //             'total' => $total,
    //             'displayColumns' => $model->getDisplayColumns()
    //         ], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message("error", $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    // }

    // public function getPractitionerQualification($uuid)
    // {
    //     $model = new PractitionerAdditionalQualificationsModel();
    //     $builder = $model->builder();
    //     $builder->where($model->getTableName() . '.uuid', $uuid);
    //     $data = $model->first();
    //     if (!$data) {
    //         return $this->respond("Practitioner qualification not found", ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    // }

    // public function createPractitionerQualification()
    // {
    //     $rules = [
    //         "registration_number" => "required",
    //         "institution" => "required",
    //         "qualification" => "required"

    //     ];

    //     if (!$this->validate($rules)) {
    //         return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $data = $this->request->getPost();
    //     $model = new PractitionerAdditionalQualificationsModel();

    //     if (!$model->insert($data)) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $id = $model->getInsertID();

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("Added additional qualification {$data['qualification']} to practitioner {$data['registration_number']}");

    //     //if registered this year, retain the person
    //     return $this->respond(['message' => 'Additional qualification created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    // }

    // public function updatePractitionerQualification($uuid)
    // {
    //     $rules = [
    //         "registration_number" => "required",
    //         "institution" => "required",
    //         "qualification" => "required"

    //     ];

    //     if (!$this->validate($rules)) {
    //         return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $data = $this->request->getVar();
    //     $data->uuid = $uuid;
    //     if (property_exists($data, "id")) {
    //         unset($data->id);
    //     }
    //     //get only the last part of the picture path
    //     if (property_exists($data, "picture")) {
    //         $splitPicturePath = explode("/", $data->picture);
    //         $data->picture = array_pop($splitPicturePath);
    //     }
    //     $model = new PractitionerAdditionalQualificationsModel();

    //     $oldData = $model->where(["uuid" => $uuid])->first();
    //     $changes = implode(", ", Utils::compareObjects($oldData, $data));

    //     if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("updated additional qualification for practitioner {$data['registration_number']}. Changes: $changes");

    //     return $this->respond(['message' => 'Practitioner additional qualification updated successfully'], ResponseInterface::HTTP_OK);
    // }

    // public function deletePractitionerQualification($uuid)
    // {
    //     $model = new PractitionerAdditionalQualificationsModel();
    //     $data = $model->where(["uuid" => $uuid])->first();

    //     if (!$model->where('uuid', $uuid)->delete()) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("Deleted additional qualification {$data['qualification']} for practitioner {$data['registration_number']}. ");

    //     return $this->respond(['message' => 'Practitioner additional qualification deleted successfully'], ResponseInterface::HTTP_OK);
    // }

    // public function restorePractitionerQualification($uuid)
    // {
    //     $model = new PractitionerAdditionalQualificationsModel();

    //     if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $data = $model->where(["uuid" => $uuid])->first();

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("Restored additional qualification {$data['qualification']} for practitioner {$data['registration_number']}. ");

    //     return $this->respond(['message' => 'Practitioner additional qualification restored successfully'], ResponseInterface::HTTP_OK);
    // }


    // public function createPractitionerWorkHistory()
    // {
    //     $rules = [
    //         "registration_number" => "required",
    //         "institution" => "required",
    //         "position" => "required",
    //         "institution_type" => "required"

    //     ];

    //     if (!$this->validate($rules)) {
    //         return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $data = $this->request->getPost();
    //     $model = new PractitionerWorkHistoryModel();

    //     if (!$model->insert($data)) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $id = $model->getInsertID();

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("Added work history position {$data['position']} at {$data['institution']} to practitioner {$data['registration_number']}");

    //     return $this->respond(['message' => 'Work history qualification created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    // }

    // public function getPractitionerWorkHistory($uuid)
    // {
    //     $model = new PractitionerWorkHistoryModel();
    //     $builder = $model->builder();
    //     $builder->where($model->getTableName() . '.uuid', $uuid);
    //     $data = $model->first();
    //     if (!$data) {
    //         return $this->respond("Practitioner work history not found", ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    // }



    // public function updatePractitionerWorkHistory($uuid)
    // {
    //     $rules = [
    //         "registration_number" => "required",
    //         "institution" => "required",
    //         "position" => "required",
    //         "institution_type" => "required"

    //     ];

    //     if (!$this->validate($rules)) {
    //         return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $data = $this->request->getVar();
    //     $data->uuid = $uuid;
    //     //the uuid is set as the id, so we have to remove it so that it doesn't get updated
    //     if (property_exists($data, "id")) {
    //         unset($data->id);
    //     }
    //     $model = new PractitionerWorkHistoryModel();

    //     $oldData = $model->where(["uuid" => $uuid])->first();
    //     $changes = implode(", ", Utils::compareObjects($oldData, $data));

    //     if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("updated work history for practitioner {$data->registration_number}. Changes: $changes");

    //     return $this->respond(['message' => 'Practitioner additional qualification updated successfully'], ResponseInterface::HTTP_OK);
    // }

    // public function deletePractitionerWorkHistory($uuid)
    // {
    //     $model = new PractitionerWorkHistoryModel();
    //     $data = $model->where(["uuid" => $uuid])->first();

    //     if (!$model->where('uuid', $uuid)->delete()) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("Deleted work history {$data['position']}  {$data['institution']} for practitioner {$data['registration_number']}. ");

    //     return $this->respond(['message' => 'Practitioner work history deleted successfully'], ResponseInterface::HTTP_OK);
    // }

    // public function restorePractitionerWorkHistory($uuid)
    // {
    //     $model = new PractitionerWorkHistoryModel();

    //     if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $data = $model->where(["uuid" => $uuid])->first();

    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("Restored work history {$data['position']} {$data['institution']} for practitioner {$data['registration_number']}. ");

    //     return $this->respond(['message' => 'Practitioner additional qualification restored successfully'], ResponseInterface::HTTP_OK);
    // }

    // public function getPractitionerWorkHistories()
    // {
    //     try {
    //         $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
    //         $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
    //         $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
    //         $param = $this->request->getVar('param');
    //         $sortBy = $this->request->getVar('sortBy') ?? "id";
    //         $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

    //         $model = new PractitionerWorkHistoryModel();
    //         $registration_number = $this->request->getGet('registration_number');
    //         $builder = $param ? $model->search($param) : $model->builder();
    //         if ($registration_number !== null) {
    //             $builder->where(["registration_number" => $registration_number]);
    //         }
    //         if ($withDeleted) {
    //             $model->withDeleted();
    //         }
    //         $builder->orderBy("$sortBy", $sortOrder);
    //         $totalBuilder = clone $builder;
    //         $total = $totalBuilder->countAllResults();
    //         $result = $builder->get($per_page, $page)->getResult();
    //         return $this->respond([
    //             'data' => $result,
    //             'total' => $total,
    //             'displayColumns' => $model->getDisplayColumns()
    //         ], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message("error", $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    // }
    //renewal

    public function getRenewals($license_uuid = null)
    {
        try {
            // $rules = [
            //     "start_date" => "if_exist|valid_date",
            //     "expiry" => "if_exist|valid_date",
            // ];
            // if (!$this->validate($rules)) {
            //     return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            // }
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new LicenseRenewalModel();

            $license_number = $this->request->getVar('license_number');
            $status = $this->request->getVar('status');
            $start_date = $this->request->getVar('start_date'); //single date or 'date1 to date2'
            $expiry = $this->request->getVar('expiry'); //single date or 'date1 to date2'
            $licenseType = $this->request->getVar('license_type');
            $created_on = $this->request->getVar('created_on'); //single date or 'date1 to date2'

            $builder = $param ? $model->search($param) : $model->builder();
            // $builder = $model->addCustomFields($builder);
            $renewal_table = $model->getTableName();

            if ($license_number !== null) {
                $builder->where("$renewal_table.license_number", $license_number);
            }
            if ($license_uuid !== null) {
                $builder->where("$renewal_table.license_uuid", $license_uuid);
                $licenseModel = new LicensesModel();
                if ($licenseType === null) {
                    $licenseType = $licenseModel->builder()->select("type")->where("uuid", $license_uuid)->get()->getRow()->type;
                }
            }
            if ($status !== null) {
                $builder->where("$renewal_table.status", $status);
            }
            if ($start_date !== null) {
                $dateRange = Utils::getDateRange($start_date);
                $builder->where("$renewal_table.start_date >=", $dateRange['start']);
                $builder->where("$renewal_table.start_date <=", $dateRange['end']);
            }
            if ($expiry !== null) {
                $dateRange = Utils::getDateRange($expiry);
                $builder->where("$renewal_table.expiry >=", $dateRange['start']);
                $builder->where("$renewal_table.expiry <=", $dateRange['end']);
            }
            if ($licenseType !== null) {
                $builder->where("$renewal_table.license_type", $licenseType);
                $model->licenseType = $licenseType; //this retrieves the correct display columns 
            }
            if ($created_on !== null) {
                $dateRange = Utils::getDateRange($created_on);
                $builder->where($model->getTableName() . '.created_on >=', $dateRange['start']);
                $builder->where($model->getTableName() . '.created_on <=', $dateRange['end']);
            }
            if (empty($licenseType)) {
                return $this->respond(['message' => "License type is required"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            //other query params might have child_ append them to the param used to filter by the license type
            //others will have renewal_ appended to the param used to filter by the subrenewal table
            //get the params   that have 'child_' appears . in the url are converted to _
            /**
             * @var array
             */
            $childParams = array_filter($this->request->getGet(), function ($key) {
                return strpos($key, 'child_') === 0;
            }, ARRAY_FILTER_USE_KEY);

            /**
             * @var array
             */
            $renewalChildParams = array_filter($this->request->getGet(), function ($key) {
                return strpos($key, 'renewal_') === 0;
            }, ARRAY_FILTER_USE_KEY);


            $licenseJoinConditions = '';
            $renewalJoinConditions = '';
            // if childParams is not empty, 
            if (!empty($childParams)) {
                $licenseDef = Utils::getLicenseSetting($licenseType);
                $renewalSubTable = $licenseDef->renewalTable;
                $licenseTypeTable = $licenseDef->table;
                /**
                 * @var array
                 */
                $joinConditions = [];
                foreach ($childParams as $key => $value) {
                    $joinConditions[] = str_replace('child_', '', $licenseTypeTable . '.' . $key) . ' = ' . "'$value'";
                }
                $licenseJoinConditions = implode(" AND ", $joinConditions);
            }
            if (!empty($renewalChildParams)) {
                $licenseDef = Utils::getLicenseSetting($licenseType);
                $renewalSubTable = $licenseDef->renewalTable;
                /**
                 * @var array
                 */
                $joinConditions = [];
                foreach ($renewalChildParams as $key => $value) {
                    $joinConditions[] = str_replace('renewal_', '', $renewalSubTable . '.' . $key) . ' = ' . "'$value'";
                }
                $renewalJoinConditions = implode(" AND ", $joinConditions);
            }

            //get the license details and the subdetails
            $builder = $model->addLicenseDetails($builder, $licenseType, $licenseJoinConditions, $renewalJoinConditions);
            $builder->orderBy($model->getTableName() . ".$sortBy", $sortOrder);
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

    public function getRenewal($uuid)
    {
        $model = new LicenseRenewalModel();
        $builder = $model->builder();
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();
        $model2 = new LicenseRenewalModel();
        $builder2 = $model2->builder();
        $builder2->where($model2->getTableName() . '.uuid', $uuid);
        $builder2 = $model->addLicenseDetails($builder2, $data['license_type']);
        $finalData = $model2->first();
        if (!$data) {
            return $this->respond("License renewal not found", ResponseInterface::HTTP_BAD_REQUEST);
        }
        return $this->respond(['data' => $finalData, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function createRenewal()
    {
        try {
            $rules = [
                "license_number" => "required",
                "start_date" => "required",
                "license_uuid" => "required",
                "status" => "required",
                "license_type" => "required"
            ];


            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $license_uuid = $this->request->getPost('license_uuid');
            $licenseType = $this->request->getPost('license_type');
            $data = $this->request->getPost();
            $model = new LicenseRenewalModel($licenseType);
            //start a transaction
            $model->db->transException(true)->transStart();

            LicenseUtils::retainLicense(
                $license_uuid,
                $data
            );

            $model->db->transComplete();

            return $this->respond(['message' => "Renewal created successfully", 'data' => ""], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateRenewal($uuid)
    {
        try {
            $rules = [
                "license_number" => "required",
                "license_uuid" => "required",
                "id" => "required",
            ];
            $renewalUuid = $this->request->getVar('id');

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = (array) $this->request->getVar();
            $data['uuid'] = $uuid;
            if (array_key_exists("id", $data)) {
                unset($data['id']);
            }

            $licenseType = $this->request->getVar('license_type');

            $model = new LicenseRenewalModel($licenseType);
            //start a transaction
            $model->db->transException(true)->transStart();

            LicenseUtils::updateRenewal(
                $renewalUuid,
                $data
            );

            $model->db->transComplete();

            return $this->respond(['message' => 'Renewal updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateBulkRenewals()
    {
        try {

            $data = json_decode($this->request->getVar('data'), true); //an array of renewals
            $status = $this->request->getVar('status');
            $results = [];
            foreach ($data as $renewal) {
                $renewalUuid = $renewal['uuid'];
                try {
                    //get the license type renewal stage required data
                    $licenseType = $renewal['license_type'];
                    log_message("info", "Renewal data: ");
                    $rules = Utils::getLicenseRenewalStageValidation($licenseType, $status);
                    $validation = \Config\Services::validation();

                    if (!$validation->setRules($rules)->run($renewal)) {
                        // Validation passed
                        throw new Exception("Validation failed");
                    }

                    unset($renewal['uuid']);
                    $renewal['status'] = $status;
                    $model = new LicenseRenewalModel($licenseType);
                    //start a transaction
                    $model->db->transException(true)->transStart();

                    LicenseUtils::updateRenewal(
                        $renewalUuid,
                        $renewal
                    );

                    $model->db->transComplete();
                    $results[] = ['id' => $renewalUuid, 'successful' => true, 'message' => 'Renewal updated successfully'];
                } catch (\Throwable $th) {
                    $results[] = ['id' => $renewalUuid, 'successful' => false, 'message' => $th->getMessage()];
                }
            }



            return $this->respond(['message' => 'Renewal updated successfully', 'data' => $results], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deleteRenewal($uuid)
    {
        try {
            $model = new LicenseRenewalModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted renewal for license number {$data['license_number']}. ");

            return $this->respond(['message' => 'License renewal deleted successfully'], ResponseInterface::HTTP_OK);
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
            $license_number = $this->request->getGet('license_number');
            $status = $this->request->getGet('status');
            $start_date = $this->request->getGet('start_date');
            $expiry = $this->request->getGet('expiry');
            $licenseType = $this->request->getGet('license_type');
            $created_on = $this->request->getGet('created_on');

            $param = $this->request->getVar('param');
            $model = new LicenseRenewalModel();


            // Validate inputs here
            $renewalTable = $model->getTableName();
            $builder = $param ? $model->search($param) : $model->builder();
            if ($license_number !== null) {
                $builder->where("$renewalTable.license_number", $license_number);
            }
            if ($status !== null) {
                $builder->where("$renewalTable.status", $status);
            }
            if ($start_date !== null) {
                $builder->where("$renewalTable.year >=", $start_date);
            }
            if ($expiry !== null) {
                $builder->where("$renewalTable.expiry <=", $expiry);
            }
            if ($licenseType !== null) {
                $builder->where("$renewalTable.license_type", $licenseType);
            }
            //other query params might have child_ append them to the param used to filter by the license type
            //others will have renewal_ appended to the param used to filter by the subrenewal table
            //get the params   that have 'child_' appears . in the url are converted to _
            /**
             * @var array
             */
            $childParams = array_filter($this->request->getGet(), function ($key) {
                return strpos($key, 'child_') === 0;
            }, ARRAY_FILTER_USE_KEY);

            /**
             * @var array
             */
            $renewalChildParams = array_filter($this->request->getGet(), function ($key) {
                return strpos($key, 'renewal_') === 0;
            }, ARRAY_FILTER_USE_KEY);


            $licenseJoinConditions = '';
            $renewalJoinConditions = '';
            // if childParams is not empty, 
            if (!empty($childParams)) {
                $licenseDef = Utils::getLicenseSetting($licenseType);
                $renewalSubTable = $licenseDef->renewalTable;
                $licenseTypeTable = $licenseDef->table;
                /**
                 * @var array
                 */
                $joinConditions = [];
                foreach ($childParams as $key => $value) {
                    $joinConditions[] = str_replace('child_', '', $licenseTypeTable . '.' . $key) . ' = ' . "'$value'";
                }
                $licenseJoinConditions = implode(" AND ", $joinConditions);
            }
            if (!empty($renewalChildParams)) {
                $licenseDef = Utils::getLicenseSetting($licenseType);
                $renewalSubTable = $licenseDef->renewalTable;
                /**
                 * @var array
                 */
                $joinConditions = [];
                foreach ($renewalChildParams as $key => $value) {
                    $joinConditions[] = str_replace('renewal_', '', $renewalSubTable . '.' . $key) . ' = ' . "'$value'";
                }
                $renewalJoinConditions = implode(" AND ", $joinConditions);
            }
            $builder = $model->addLicenseDetails($builder, $licenseType, $licenseJoinConditions, $renewalJoinConditions);
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

    public function getLicenseRenewalFormFields($licenseType)
    {
        $licenseModel = new LicenseRenewalModel();
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $renewalStages = (array) $licenseDef->renewalStages;
            //get the keys of the renewal stages
            $renewalStagesKeys = array_keys($renewalStages);
            $status = [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ];
            foreach ($renewalStagesKeys as $key) {
                $status["options"][] = [
                    "key" => $key,
                    "value" => $key
                ];
            }
            $modelFields = $licenseModel->getFormFields();
            $modelFields[] = $status;
            //add the general license fields
            $licenseFields = array_merge($modelFields, $licenseDef->renewalFields);
            return $this->respond([
                'data' => $licenseFields
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getBasicStatistics($licenseType = null)
    {
        try {
            $model = new LicensesModel();
            $results = [];
            $fields = $model->getBasicStatisticsFields($licenseType);
            $licenseJoinConditions = '';
            $licenseTypeTable = '';
            //get the params   that have 'child_' appears . in the url are converted to _
            /**
             * @var array
             */
            $childParams = array_filter($this->request->getGet(), function ($key) {
                return strpos($key, 'child_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            // if childParams is not empty, 
            if (!empty($childParams)) {
                $licenseDef = Utils::getLicenseSetting($licenseType);
                $licenseTypeTable = $licenseDef->table;
                /**
                 * @var array
                 */
                $joinConditions = [];
                foreach ($childParams as $key => $value) {
                    $joinConditions[] = str_replace('child_', '', $licenseTypeTable . '.' . $key) . ' = ' . "'$value'";
                }
                $licenseJoinConditions = implode(" AND ", $joinConditions);
            }
            foreach ($fields['default'] as $field) {
                $builder = $model->builder();
                $builder->select([$field->name, "COUNT(*) as count"]);
                if ($licenseType !== null) {
                    $builder->where("type", $licenseType);
                }
                //if the field has an alias, use it
                if (strpos($field->name, " as ") !== false) {
                    $field->name = explode(" as ", $field->name)[1];
                }
                if ($licenseJoinConditions) {
                    $builder->join($licenseTypeTable, "$licenseTypeTable.license_number = licenses.license_number");
                    $builder->where($licenseJoinConditions);
                }
                $builder->groupBy($field->name);
                $result = $builder->get()->getResult();
                //replace null with 'Null'
                $result = array_map(function ($item) use ($field) {
                    $item->{$field->name} = empty($item->{$field->name}) ? 'Null' : $item->{$field->name};
                    return $item;
                }, $result);
                $results[$field->name] = [
                    "label" => $field->label,
                    "type" => $field->type,
                    "data" => $result,
                    "labelProperty" => $field->name,
                    "valueProperty" => "count",
                    "name" => $field->name,
                    "xAxisLabel" => $field->xAxisLabel,
                    "yAxisLabel" => $field->yAxisLabel,
                ];
            }
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $table = $licenseDef->table;
            foreach ($fields['custom'] as $field) {
                $builder = $model->builder($table);
                $builder->select([$field->name, "COUNT(*) as count"]);

                //if the field has an alias, use it
                if (strpos($field->name, " as ") !== false) {
                    $field->name = explode(" as ", $field->name)[1];
                }
                if ($licenseJoinConditions) {
                    $builder->where($licenseJoinConditions);
                }
                $builder->groupBy($field->name);
                $result = $builder->get()->getResult();
                //replace null with 'Null'
                $result = array_map(function ($item) use ($field) {
                    $item->{$field->name} = empty($item->{$field->name}) ? 'Null' : $item->{$field->name};
                    return $item;
                }, $result);
                $results[$field->name] = [
                    "label" => $field->label,
                    "type" => $field->type,
                    "data" => $result,
                    "labelProperty" => $field->name,
                    "valueProperty" => "count",
                    "name" => $field->name,
                    "xAxisLabel" => $field->xAxisLabel,
                    "yAxisLabel" => $field->yAxisLabel,
                ];
            }
            return $this->respond(['data' => $results], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }
}
