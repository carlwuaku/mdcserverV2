<?php

namespace App\Controllers;

use App\Helpers\CpdUtils;
use App\Models\Cpd\CpdProviderModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\Cpd\CpdModel;
use App\Models\Cpd\CpdAttendanceModel;
use App\Models\ActivitiesModel;
use \Exception;
use App\Helpers\Utils;
class CpdController extends ResourceController
{

    public function createCpd()
    {
        try {
            $rules = [
                "topic" => "required|is_unique[cpd_topics.topic]",
                "provider_uuid" => "required|is_not_unique[cpd_providers.uuid]",
                "credits" => "required|is_natural_no_zero",
                "category" => "required",
                "date" => "required|valid_date",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $userId = auth()->id();
            $data = $this->request->getPost();
            $data['created_by'] = $userId;
            $model = new CpdModel();
            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created cpd {$data['topic']}.", null, "cpd");

            return $this->respond(['message' => 'Cpd topic created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateCpd($uuid)
    {
        try {
            $rules = [
                "topic" => "permit_empty|is_unique[cpd_topics.topic,uuid,$uuid]",
                "provider_uuid" => "permit_empty|is_not_unique[cpd_providers.uuid]",
                "credits" => "permit_empty|is_natural_no_zero",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();

            $model = new CpdModel();
            $oldData = $model->where(["uuid" => $uuid])->first();
            if (!$oldData) {
                throw new Exception("Cpd not found");
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $update = $model->builder()->where(['uuid' => $uuid])->update($data);
            if (!$update) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated cpd {$oldData['topic']}. Changes: $changes", null, "cpd");

            return $this->respond(['message' => 'CPD updated successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteCpd($uuid)
    {
        try {
            $model = new CpdModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted cpd {$data['topic']}.", null, "cpd");

            return $this->respond(['message' => 'CPD deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function countCpds()
    {
        try {

            $param = $this->request->getVar('param');
            $model = new CpdModel();
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

    public function restoreCpd($uuid)
    {
        try {
            $model = new CpdModel();
            if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $model->where(["uuid" => $uuid])->first();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Restored cpd {$data['topic']} from recycle bin");

            return $this->respond(['message' => 'Cpd restored successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }


    }

    public function getCpd($uuid)
    {
        $model = new CpdModel();
        $providerModel = new CpdProviderModel();
        $builder = $model->builder();
        $builder = $model->addCustomFields($builder);
        $data = $builder->where(["cpd_topics.uuid" => $uuid])->get()->getRow();
        if (!$data) {
            return $this->respond("CPD not found", ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getCpds()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $year = $this->request->getVar('year') ?? null;
            $providerUuid = $this->request->getVar('provider_uuid') ?? null;

            $model = new CpdModel();


            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            if ($year) {
                $builder->where("YEAR(date)", $year);
            }
            if ($providerUuid) {
                $builder->where("provider_uuid", $providerUuid);
            }

            $tableName = $model->table;
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
                'displayColumns' => $model->getDisplayColumns(),
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createCpdProvider()
    {
        try {
            $rules = [
                "name" => "required|is_unique[cpd_providers.name]",
                "phone" => "required",
                "email" => "required|valid_email",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getPost();

            $model = new CpdProviderModel();
            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created cpd provider {$data['name']}.", null, "cpd");

            return $this->respond(['message' => 'Cpd provider created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateCpdProvider($uuid)
    {
        try {
            $rules = [
                "name" => "permit_empty|is_unique[cpd_providers.name,uuid,$uuid]",
                "email" => "permit_empty|valid_email",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();

            $model = new CpdProviderModel();
            $oldData = $model->where(["uuid" => $uuid])->first();
            if (!$oldData) {
                throw new Exception("Cpd not found");
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $update = $model->builder()->where(['uuid' => $uuid])->update($data);
            if (!$update) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated cpd provider {$oldData['name']}. Changes: $changes", null, "cpd");

            return $this->respond(['message' => 'CPD updated successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteCpdProvider($uuid)
    {
        try {
            $model = new CpdProviderModel();
            $data = $model->where(["uuid" => $uuid])->first();
            if (!$data) {
                return $this->respond(['message' => "CPD provider not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted cpd provider {$data['name']}.", null, "cpd");

            return $this->respond(['message' => 'CPD provider deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function countCpdProviders()
    {
        try {

            $param = $this->request->getVar('param');
            $model = new CpdProviderModel();
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

    public function restoreCpdProvider($uuid)
    {
        try {
            $model = new CpdProviderModel();
            if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $model->where(["uuid" => $uuid])->first();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Restored cpd provider {$data['topic']} from recycle bin");

            return $this->respond(['message' => 'Cpd provider restored successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }


    }

    public function getCpdProvider($uuid)
    {
        $model = new CpdProviderModel();
        $data = $model->where(["uuid" => $uuid])->first();
        if (!$data) {
            return $this->respond("CPD not found", ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getCpdProviders()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new CpdProviderModel();


            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);

            $tableName = $model->table;
            $builder->orderBy("$tableName.$sortBy", $sortOrder);

            if ($withDeleted) {
                $model->withDeleted();
            }
            log_message("error", $builder->getCompiledSelect(false));
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
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createCpdAttendance()
    {
        try {
            $rules = [
                "cpd_uuid" => "required|is_not_unique[cpd_topics.uuid]",
                "license_number.*" => "required|is_not_unique[licenses.license_number]",
                "attendance_date" => "required|valid_date"
            ];

            if (!$this->validate($rules)) {
                return $this->respond(['message' => $this->validator->getErrors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->request->getJSON(true);
            $cpdModel = new CpdModel();
            $cpd = $cpdModel->where(["uuid" => $data['cpd_uuid']])->first();
            if (!$cpd) {
                return $this->respond(['message' => "CPD topic not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }
            // $data['created_by'] = $userId;
            $model = new CpdAttendanceModel();
            $failed = [];
            foreach ($data['license_number'] as $license) {
                $licenseData = [
                    "cpd_uuid" => $data['cpd_uuid'],
                    "license_number" => $license,
                    "attendance_date" => $data['attendance_date'],
                    "venue" => $data['venue']
                ];
                if (!$model->insert($licenseData)) {
                    $failed[] = $license;
                } else {
                    try {
                        /** @var ActivitiesModel $activitiesModel */
                        $activitiesModel = new ActivitiesModel();
                        $activitiesModel->logActivity("Created cpd attendance for {$license} {$cpd['topic']}.", null, "cpd");
                    } catch (Exception $e) {
                        log_message("error", $e->getMessage());
                    }
                }
            }

            $id = $model->getInsertID();
            $failedList = implode(", ", $failed);
            $message = count($failed) > 0 ? count($failed) . " records for ($failedList) failed to save. Please try those ones again" : "Cpd attendance created successfully";

            return $this->respond(['message' => $message, 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateCpdAttendance($uuid)
    {
        try {
            $rules = [
                "cpd_id" => "permit_empty|is_not_unique[cpd_topics.id]",
                "license_number" => "permit_empty|is_not_unique[license_numbers.license_number]",
                "attendance_date" => "permit_empty|valid_date",
            ];

            if (!$this->validate($rules)) {
                return $this->respond(['message' => $this->validator->getErrors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();
            $cpdModel = new CpdModel();
            $cpd = $cpdModel->where(["id" => $this->request->getPost('cpd_id')])->first();

            $model = new CpdAttendanceModel();
            $oldData = $model->where(["uuid" => $uuid])->first();
            if (!$oldData) {
                throw new Exception("Cpd attendance not found");
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $update = $model->builder()->where(['uuid' => $uuid])->update($data);
            if (!$update) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated cpd attendance for {$oldData['license_number']} {$cpd->topic}. Changes: $changes", null, "cpd");

            return $this->respond(['message' => 'CPD attendance updated successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteCpdAttendance($uuid)
    {
        try {
            $model = new CpdAttendanceModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted cpd attendance for {$data['license_number']}.", null, "cpd");

            return $this->respond(['message' => 'CPD attendance deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function countCpdAttendances()
    {
        try {

            $param = $this->request->getVar('param');
            $model = new CpdAttendanceModel();
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

    public function restoreCpdAttendance($uuid)
    {
        try {
            $model = new CpdAttendanceModel();
            if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $model->where(["uuid" => $uuid])->first();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Restored cpd attendance for {$data['license_number']} from recycle bin");

            return $this->respond(['message' => 'Cpd attendance restored successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }


    }

    public function getCpdAttendance($uuid)
    {
        $model = new CpdAttendanceModel();
        $builder = $model->builder();
        $builder = $model->addCustomFields($builder);
        $data = $builder->where(["cpd_attendance.uuid" => $uuid])->get()->getRow();
        if (!$data) {
            return $this->respond("CPD attendance not found", ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getCpdAttendances()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $cpdId = $this->request->getVar('cpdUuid') ?? null;
            $licenseNumber = $this->request->getVar('license_number') ?? null;


            $model = new CpdAttendanceModel();


            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            if ($cpdId) {
                $builder->where("cpd_uuid", $cpdId);
            }
            if ($licenseNumber) {
                $builder->where("{$model->table}.license_number", $licenseNumber);
            }

            $tableName = $model->table;
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
                'displayColumns' => $model->getDisplayColumns(),
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getLicenseCpdAttendances()
    {
        try {

            $model = new CpdAttendanceModel();
            $licenseNumber = $this->request->getVar('license_number');
            if (empty($licenseNumber)) {
                return $this->respond(['message' => "License number is required"], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $year = $this->request->getVar('year');
            if (empty($year)) {
                return $this->respond(['message' => "Year is required"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $result = CpdUtils::getCPDAttendanceAndScores($licenseNumber, $year);
            return $this->respond([
                'data' => $result,
                'total' => count($result['attendance']),
                'displayColumns' => $model->getDisplayColumns(),
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }
}
