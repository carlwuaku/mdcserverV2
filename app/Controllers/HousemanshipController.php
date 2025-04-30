<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\LicenseUtils;
use App\Models\Housemanship\HousemanshipApplicationDetailsModel;
use App\Models\Housemanship\HousemanshipApplicationModel;
use App\Models\Housemanship\HousemanshipDisciplinesModel;
use App\Models\Housemanship\HousemanshipFacilitiesModel;
use App\Models\Housemanship\HousemanshipFacilityAvailabilityModel;
use App\Models\Housemanship\HousemanshipFacilityCapacitiesModel;
use App\Models\Housemanship\HousemanshipPostingDetailsModel;
use App\Models\Housemanship\HousemanshipPostingsModel;
use App\Models\Licenses\LicensesModel;
use ArrayObject;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ActivitiesModel;
use \Exception;
use App\Helpers\Utils;
use App\Helpers\Enums\HousemanshipSetting;

class HousemanshipController extends ResourceController
{
    private $activityModule = "housemanship";
    public function createHousemanshipFacility()
    {
        try {
            $rules = [
                "name" => "required|is_unique[housemanship_facilities.name]",
                "region" => "required|is_not_unique[regions.name]",
                "email" => "permit_empty|valid_email",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getPost();

            $model = new HousemanshipFacilitiesModel();
            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created housemanship facility {$data['name']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship facility created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateHousemanshipFacility($uuid)
    {
        try {
            $rules = [
                "name" => "permit_empty|is_unique[housemanship_facilities.name,uuid,$uuid]",
                "email" => "permit_empty|valid_email",
                "region" => "permit_empty|is_not_unique[regions.name]",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();

            $model = new HousemanshipFacilitiesModel();
            $oldData = $model->where(["uuid" => $uuid])->first();
            if (!$oldData) {
                throw new Exception("Housemanship facility not found");
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $update = $model->builder()->where(['uuid' => $uuid])->update($data);
            if (!$update) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated housemanship facility {$oldData['name']}. Changes: $changes", null, "cpd");

            return $this->respond(['message' => 'Housemanship facility updated successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteHousemanshipFacility($uuid)
    {
        try {
            $model = new HousemanshipFacilitiesModel();
            $data = $model->where(["uuid" => $uuid])->first();
            if (!$data) {
                return $this->respond(['message' => "Housemanship facility not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted Housemanship facility {$data['name']}.", null, "cpd");

            return $this->respond(['message' => 'Housemanship facility deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function countHousemanshipFacilities()
    {
        try {

            $param = $this->request->getVar('param');
            $model = new HousemanshipFacilitiesModel();
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



    public function getHousemanshipFacility($uuid)
    {
        $model = new HousemanshipFacilitiesModel();
        $data = $model->where(["uuid" => $uuid])->first();
        if (!$data) {
            return $this->respond("Housemanship facility not found", ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getHousemanshipFacilities()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "name";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new HousemanshipFacilitiesModel();


            $builder = $param ? $model->search($param) : $model->builder();
            // $builder = $model->addCustomFields($builder);

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

    public function getHousemanshipFacilityFormFields()
    {
        $model = new HousemanshipFacilitiesModel();
        try {
            return $this->respond([
                'data' => $model->getFormFields()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }


    public function createHousemanshipFacilityCapacity()
    {
        try {
            $model = new HousemanshipFacilityCapacitiesModel();
            $rules = [
                "year" => "required|integer|exact_length[4]",
                "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
                "capacity" => "required|is_natural",
                "discipline" => "required|is_not_unique[housemanship_disciplines.name]",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            //delete existing record and insert a new one
            $facilityName = $this->request->getPost('facility_name');
            $year = $this->request->getPost('year');
            $discipline = $this->request->getPost('discipline');
            $model->where("facility_name", $facilityName)->where("year", $year)->where("discipline", $discipline)->delete();

            $data = $this->request->getPost();


            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created housemanship facility capacity for {$data['facility_name']} {$data['year']} - {$data['discipline']} {$data['capacity']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship facility capacity created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateHousemanshipFacilityCapacity($id)
    {
        try {
            $rules = [
                "year" => "permit_empty|integer|exact_length[4]",
                "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
                "capacity" => "permit_empty|is_natural",
                "discipline" => "permit_empty|alpha_numeric_punct",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();

            $model = new HousemanshipFacilityCapacitiesModel();
            $oldData = $model->where(["id" => $id])->first();
            if (!$oldData) {
                throw new Exception("Record not found");
            }
            if ($oldData['facility_name'] !== $data->facility_name) {
                throw new Exception("Record facility does not match");
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $update = $model->builder()->where(['id' => $id])->update($data);
            if (!$update) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated housemanship facility capacity {$oldData['facility_name']}. Changes: $changes", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship facility capacity updated successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteHousemanshipFacilityCapacity($id)
    {
        try {
            $model = new HousemanshipFacilityCapacitiesModel();
            $data = $model->where(["id" => $id])->first();
            if (!$data) {
                return $this->respond(['message' => "Record not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (!$model->where('id', $id)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted Housemanship facility capacity {$data['facility_name']} {$data['year']} - {$data['discipline']} {$data['capacity']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship facility capacity deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function getHousemanshipFacilityCapacities()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "facility_name, year, discipline";
            $sortOrder = $this->request->getVar('sortOrder') ?? "desc";

            $model = new HousemanshipFacilityCapacitiesModel();
            //allow filtering by facility_name, year, discipline, capacity
            $filterArray = $model->createArrayFromAllowedFields((array) $this->request->getGet());


            $builder = $param ? $model->search($param) : $model->builder();
            array_map(function ($value, $key) use ($builder) {
                $builder->where($key, $value);
            }, $filterArray, array_keys($filterArray));

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

    public function getHousemanshipFacilityCapacityFormFields()
    {
        $model = new HousemanshipFacilityCapacitiesModel();
        try {

            return $this->respond([
                'data' => $model->getFormFields()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createHousemanshipFacilityAvailability()
    {
        $model = new HousemanshipFacilityAvailabilityModel();
        try {
            $categories = implode(",", $model->getAvailabilityCategories());
            $rules = [
                "year" => "required|integer|exact_length[4]",
                "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
                "available" => "required|in_list[0,1]",
                "category" => "required|in_list[$categories]",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            //delete existing record and insert a new one
            $facilityName = $this->request->getPost('facility_name');
            $year = $this->request->getPost('year');
            $category = $this->request->getPost('category');
            $model->where("facility_name", $facilityName)->where("year", $year)->where("category", $category)->delete();

            $data = $this->request->getPost();


            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created housemanship facility availability for {$data['facility_name']} {$data['year']} - {$data['category']} {$data['available']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship facility availability created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateHousemanshipFacilityAvailability($id)
    {
        $model = new HousemanshipFacilityAvailabilityModel();
        try {
            $categories = implode(",", $model->getAvailabilityCategories());
            $rules = [
                "year" => "permit_empty|integer|exact_length[4]",
                "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
                "available" => "permit_empty|is_natural",
                "category" => "permit_empty|in_list[$categories]",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();

            $oldData = $model->where(["id" => $id])->first();
            if (!$oldData) {
                throw new Exception("Record not found");
            }
            if ($oldData['facility_name'] !== $data['facility_name']) {
                throw new Exception("Record facility does not match");
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $update = $model->builder()->where(['id' => $id])->update($data);
            if (!$update) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated housemanship facility availability {$oldData['name']}. Changes: $changes", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship facility availability updated successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteHousemanshipFacilityAvailability($id)
    {
        try {
            $model = new HousemanshipFacilityAvailabilityModel();
            $data = $model->where(["id" => $id])->first();
            if (!$data) {
                return $this->respond(['message' => "Record not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (!$model->where('id', $id)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted Housemanship facility availability {$data['facility_name']} {$data['year']} - {$data['category']} {$data['availability']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship facility availability deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function getHousemanshipFacilityAvailabilities()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "facility_name, year, category";
            $sortOrder = $this->request->getVar('sortOrder') ?? "desc";

            $model = new HousemanshipFacilityAvailabilityModel();
            //allow filtering by facility_name, year, discipline, capacity
            $filterArray = $model->createArrayFromAllowedFields((array) $this->request->getGet());


            $builder = $param ? $model->search($param) : $model->builder();
            array_map(function ($value, $key) use ($builder) {
                $builder->where($key, $value);
            }, $filterArray, array_keys($filterArray));

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

    public function getHousemanshipFacilityAvailabilityFormFields()
    {
        $model = new HousemanshipFacilityAvailabilityModel();
        try {

            return $this->respond([
                'data' => $model->getFormFields()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }


    public function createHousemanshipDiscipline()
    {
        $model = new HousemanshipDisciplinesModel();
        try {
            $rules = [
                "name" => "required"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getPost();

            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created housemanship discipline", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship discipline created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateHousemanshipDiscipline($id)
    {
        $model = new HousemanshipDisciplinesModel();
        try {
            $rules = [
                "name" => "required|is_unique[housemanship_disciplines.name,id,$id]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();

            $oldData = $model->where(["id" => $id])->first();
            if (!$oldData) {
                throw new Exception("Record not found");
            }

            $changes = implode(", ", Utils::compareObjects($oldData, $data));

            $update = $model->builder()->where(['id' => $id])->update($data);
            if (!$update) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated housemanship discipline {$oldData['name']}. Changes: $changes", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship discipline updated successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteHousemanshipDiscipline($id)
    {
        try {
            $model = new HousemanshipDisciplinesModel();
            $data = $model->where(["id" => $id])->first();
            if (!$data) {
                return $this->respond(['message' => "Record not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (!$model->where('id', $id)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted Housemanship discipline {$data['name']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship discipline deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function restoreHousemanshipDiscipline($id)
    {
        try {
            $model = new HousemanshipDisciplinesModel();
            if (!$model->builder()->where(['id' => $id])->update(['deleted_at' => null])) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $model->where(["id" => $id])->first();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Restored license {$data['name']} from recycle bin");

            return $this->respond(['message' => 'Discipline restored successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getHousemanshipDisciplines()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "name";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new HousemanshipDisciplinesModel();
            //allow filtering by facility_name, year, discipline, capacity
            $filterArray = $model->createArrayFromAllowedFields((array) $this->request->getGet());


            $builder = $param ? $model->search($param) : $model->builder();
            array_map(function ($value, $key) use ($builder) {
                $builder->where($key, $value);
            }, $filterArray, array_keys($filterArray));

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

    public function getHousemanshipDisciplineFormFields()
    {
        $model = new HousemanshipDisciplinesModel();
        try {

            return $this->respond([
                'data' => $model->getFormFields()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createHousemanshipPosting()
    {
        try {
            $rules = [
                "license_number" => "required|is_not_unique[licenses.license_number]",
                "session" => "required",
                "year" => "required|integer|exact_length[4]",
                "letter_template" => "required|is_not_unique[print_templates.template_name]",
                "details" => "required"
            ];

            if (!$this->validate($rules)) {
                $message = implode(" ", array_values($this->validator->getErrors()));
                return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $license = LicenseUtils::getLicenseDetails($this->request->getVar('license_number'));
            if (!$license) {
                throw new Exception("License not found");
            }

            $data = $this->request->getJSON(true);
            $data['category'] = $license['category'];
            $data['type'] = $license['practitioner_type'];
            $data['practitioner_details'] = json_encode($license);
            $model = new HousemanshipPostingsModel();
            $model->db->transException(true)->transStart();
            $postingId = $model->insert($data);
            $posting = $model->where(['id' => $postingId])->first();
            if (!$posting) {
                throw new Exception("Failed to create posting");
            }
            $postingUuid = $posting['uuid'];
            /**
             * @var \App\Models\Housemanship\HousemanshipPostingDetailsModel[] $details
             */
            $details = $data['details'];//array
            $detailsValidationRules = [
                "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
                "discipline" => "required|is_not_unique[housemanship_disciplines.name]",
                "start_date" => "permit_empty|valid_date",
                "end_date" => "permit_empty|valid_date",
            ];
            foreach ($details as $postingDetail) {
                $postingDetail = (array) $postingDetail;
                $postingDetail['posting_uuid'] = $postingUuid;
                $validation = \Config\Services::validation();

                if (!$validation->setRules($detailsValidationRules)->run($postingDetail)) {
                    throw new Exception("Validation failed");
                }
                //get the facility details
                $facilityModel = new HousemanshipFacilitiesModel();
                $facility = $facilityModel->where(['name' => $postingDetail['facility_name']])->first();
                if (!$facility) {
                    throw new Exception("Facility not found");
                }
                $postingDetail['facility_region'] = $facility['region'];
                $postingDetail['facility_details'] = json_encode($facility);
                $postingDetailsModel = new HousemanshipPostingDetailsModel();
                $postingDetailsModel->insert($postingDetail);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Added housemanship session {$data['session']} posting for {$data['license_number']}", null, $this->activityModule);

            $model->db->transComplete();

            return $this->respond(['message' => "Housemanship posting created successfully for {$data['license_number']}", 'data' => ""], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateHousemanshipPosting($uuid)
    {
        try {
            $rules = [
                "license_number" => "required|is_not_unique[licenses.license_number]",
                "session" => "required",
                "year" => "required|integer|exact_length[4]",
                "letter_template" => "required|is_not_unique[print_templates.template_name]",
                "details" => "required"
            ];

            if (!$this->validate($rules)) {
                $message = implode(" ", array_values($this->validator->getErrors()));
                return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $license = LicenseUtils::getLicenseDetails($this->request->getVar('license_number'));
            if (!$license) {
                throw new Exception("License not found");
            }
            $data = $this->request->getJSON(true);
            $data['category'] = $license['category'];
            $data['type'] = $license['practitioner_type'];
            $data['practitioner_details'] = json_encode($license);
            $model = new HousemanshipPostingsModel();

            $model->db->transException(true)->transStart();
            /**
             * @var \App\Models\Housemanship\HousemanshipPostingDetailsModel[] $details
             */
            $details = $data['details'];//array
            unset($data['details']);
            $model->builder()->where(['uuid' => $uuid])->update($data);

            $postingDetailsModel = new HousemanshipPostingDetailsModel();
            //delete existing details
            $postingDetailsModel->builder()->where(['posting_uuid' => $uuid])->delete();
            $postingUuid = $uuid;

            $detailsValidationRules = [
                "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
                "discipline" => "required|is_not_unique[housemanship_disciplines.name]",
                "start_date" => "permit_empty|valid_date",
                "end_date" => "permit_empty|valid_date",
            ];

            foreach ($details as $postingDetail) {
                $postingDetail = (array) $postingDetail;
                $postingDetail['posting_uuid'] = $postingUuid;
                $validation = \Config\Services::validation();

                if (!$validation->setRules($detailsValidationRules)->run($postingDetail)) {
                    throw new Exception("Validation failed");
                }
                //get the facility details
                $facilityModel = new HousemanshipFacilitiesModel();
                $facility = $facilityModel->where(['name' => $postingDetail['facility_name']])->first();
                if (!$facility) {
                    throw new Exception("Facility not found");
                }
                $postingDetail['facility_region'] = $facility['region'];
                $postingDetail['facility_details'] = json_encode($facility);

                $postingDetailsModel->insert($postingDetail);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated housemanship session {$data['session']} posting for {$data['license_number']}", null, $this->activityModule);

            $model->db->transComplete();

            return $this->respond(['message' => "Housemanship posting updated successfully for {$data['license_number']}", 'data' => ""], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }



    public function deleteHousemanshipPosting($uuid)
    {
        try {
            $model = new HousemanshipPostingsModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted housemanship posting for {$data['license_number']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship posting deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function countHousemanshipPostings()
    {
        try {

            $param = $this->request->getVar('param');
            $model = new HousemanshipPostingsModel();
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


    /**
     * get a single housemanship posting with its details flattened. this will mostly be used for filling the form to edit the posting
     * @param mixed $uuid
     * @throws \Exception
     * @return ResponseInterface
     */
    public function getHousemanshipPosting($uuid)
    {
        try {
            $model = new HousemanshipPostingsModel();
            $detailsModel = new HousemanshipPostingDetailsModel();
            $data = $model->where(["uuid" => $uuid])->first();
            if (!$data) {
                throw new Exception("Housemanship posting not found");
            }
            $details = $detailsModel->where(["posting_uuid" => $uuid])->findAll();
            for ($i = 0; $i < count($details); $i++) {
                $data["posting_detail-facility_name-$i"] = $details[$i]['facility_name'];
                $data["posting_detail-discipline-$i"] = $details[$i]['discipline'];
                $data["posting_detail-start_date-$i"] = $details[$i]['start_date'];
                $data["posting_detail-end_date-$i"] = $details[$i]['end_date'];
                $data["posting_detail-facility_region-$i"] = $details[$i]['facility_region'];
            }

            return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);

        }
    }

    public function getHousemanshipPostings()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new HousemanshipPostingsModel();
            $detailsModel = new HousemanshipPostingDetailsModel();

            $filterArray = $model->createArrayFromAllowedFields((array) $this->request->getGet());
            // Validate inputs here
            $tableName = $model->table;
            $builder = $param ? $model->search($param) : $model->builder()->select("$tableName.*");
            $builder = $model->addPractitionerDetailsFields($builder);
            array_map(function ($value, $key) use ($builder, $tableName) {
                $builder->where($tableName . "." . $key, $value);
            }, $filterArray, array_keys($filterArray));

            $builder->orderBy("$tableName.$sortBy", $sortOrder);

            if ($withDeleted) {
                $model->withDeleted();
            }

            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $displayColumns = $model->getDisplayColumns();
            // 1. Get parent records (housemanship postings)
            $parentRecords = $builder->get($per_page, $page)->getResult();

            // 2. Extract all parent IDs
            $parentIds = array_map(function ($record) {
                return $record->uuid;
            }, $parentRecords);

            // 3. Get all related child records in a single query
            $childRecords = [];
            if (!empty($parentIds)) {
                $childRecords = $detailsModel->whereIn('posting_uuid', $parentIds)->findAll();
            }

            // 4. Group child records by parent_id for quick access
            $childrenByParentId = [];
            foreach ($childRecords as $child) {
                if (!isset($childrenByParentId[$child['posting_uuid']])) {
                    $childrenByParentId[$child['posting_uuid']] = [];
                }
                $childrenByParentId[$child['posting_uuid']][] = $child;
            }

            // 5. Combine parent and child data
            foreach ($parentRecords as $parent) {
                $children = $childrenByParentId[$parent->uuid] ?? [];
                foreach ($children as $index => $child) {
                    // Convert child object to array to manipulate
                    $childArray = (array) $child;
                    foreach ($childArray as $key => $value) {
                        if (!in_array($key, ['posting_uuid', 'id', 'facility_details'])) { // Skip the join key
                            // Add child fields to parent
                            $fieldName = $key . "_" . ($index + 1);
                            if (!in_array($fieldName, $displayColumns)) {
                                $displayColumns[] = $fieldName;
                            }
                            $parent->$fieldName = $value;
                        }
                    }
                }
            }

            return $this->respond([
                'data' => $parentRecords,
                'total' => $total,
                'displayColumns' => $displayColumns,
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getHousemanshipPostingFormFields($session)
    {
        $model = new HousemanshipPostingsModel();
        $detailsModel = new HousemanshipPostingDetailsModel();
        try {
            $sessionSetting = Utils::getHousemanshipSetting(HousemanshipSetting::SESSIONS);
            if (!$sessionSetting) {
                throw new Exception("Session setting not found");
            }
            if (!array_key_exists($session, $sessionSetting)) {
                throw new Exception("Session not found");
            }
            $numberOfRequiredFacilities = (int) $sessionSetting[$session]['number_of_facilities'];
            $mainFields = $model->getFormFields();
            $detailsFields = $detailsModel->getFormFields();
            //add the details fields to the main fields the number of times required
            for ($i = 0; $i < $numberOfRequiredFacilities; $i++) {
                $mainFields[] = [
                    "label" => "Discipline " . ($i + 1),
                    "name" => "",
                    "type" => "label",
                    "hint" => "",
                    "options" => [],
                    "value" => "",
                    "required" => false
                ];
                $detail = [];
                foreach ($detailsFields as $detailsField) {
                    $detailsField['name'] = "posting_detail-{$detailsField['name']}-$i";
                    $detail[] = $detailsField;
                }

                $mainFields[] = $detail;
            }
            return $this->respond([
                'data' => $mainFields
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createHousemanshipPostingApplication()
    {
        try {
            $rules = [
                "license_number" => "required|is_not_unique[licenses.license_number]",
                "session" => "required",
                "year" => "required|integer|exact_length[4]",
                "details" => "required"
            ];

            if (!$this->validate($rules)) {
                $message = implode(" ", array_values($this->validator->getErrors()));
                return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $license = LicenseUtils::getLicenseDetails($this->request->getVar('license_number'));
            if (!$license) {
                return $this->respond(['message' => "License not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getJSON(true);
            $data['category'] = $license['category'];
            $data['type'] = $license['practitioner_type'];
            $model = new HousemanshipApplicationModel();
            $model->db->transException(true)->transStart();
            $applicationId = $model->insert($data);
            $application = $model->where(['id' => $applicationId])->first();
            if (!$application) {
                throw new Exception("Failed to create application");
            }
            $applicationUuid = $application['uuid'];
            /**
             * @var \App\Models\Housemanship\HousemanshipApplicationDetailsModel[] $details
             */
            $details = $data['details'];//array
            $detailsValidationRules = [
                "first_choice" => "required|is_not_unique[housemanship_facilities.name]",
                "second_choice" => "required|is_not_unique[housemanship_facilities.name]|differs[first_choice]",
                "discipline" => "required|is_not_unique[housemanship_disciplines.name]"
            ];
            foreach ($details as $applicationDetail) {
                $applicationDetail = (array) $applicationDetail;
                $applicationDetail['application_uuid'] = $applicationUuid;
                $validation = \Config\Services::validation();

                if (!$validation->setRules($detailsValidationRules)->run($applicationDetail)) {
                    $message = implode(" ", array_values($validation->getErrors()));
                    return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
                }
                //get the facility details
                $facilityModel = new HousemanshipFacilitiesModel();
                $firstChoice = $facilityModel->where(['name' => $applicationDetail['first_choice']])->first();
                if (!$firstChoice) {
                    log_message("error", "First choice facility not found {$applicationDetail['first_choice']}");
                    $message = "First choice facility not found ";
                    return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
                }

                $secondChoice = $facilityModel->where(['name' => $applicationDetail['second_choice']])->first();
                if (!$secondChoice) {
                    log_message("error", "Second choice facility not found {$applicationDetail['second_choice']}");
                    $message = "Second choice facility not found ";
                    return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
                }
                $applicationDetail['first_choice_region'] = $firstChoice['region'];
                $applicationDetail['second_choice_region'] = $secondChoice['region'];
                $applicationDetailsModel = new HousemanshipApplicationDetailsModel();
                $applicationDetailsModel->insert($applicationDetail);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Added housemanship session {$data['session']} application for {$data['license_number']}", null, $this->activityModule);

            $model->db->transComplete();

            return $this->respond(['message' => "Housemanship application created successfully for {$data['license_number']}", 'data' => ""], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateHousemanshipPostingApplication($uuid)
    {
        try {
            $rules = [
                "license_number" => "required|is_not_unique[licenses.license_number]",
                "session" => "required",
                "year" => "required|integer|exact_length[4]",
                "details" => "required"
            ];

            if (!$this->validate($rules)) {
                $message = implode(" ", array_values($this->validator->getErrors()));
                return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $license = LicenseUtils::getLicenseDetails($this->request->getVar('license_number'));
            if (!$license) {
                throw new Exception("License not found");
            }
            $data = $this->request->getJSON(true);
            $data['category'] = $license['category'];
            $data['type'] = $license['practitioner_type'];

            $model = new HousemanshipApplicationModel();

            $model->db->transException(true)->transStart();
            /**
             * @var \App\Models\Housemanship\HousemanshipApplicationDetailsModel[] $details
             */
            $details = $data['details'];//array
            unset($data['details']);
            $model->builder()->where(['uuid' => $uuid])->update($data);

            $applicationDetailsModel = new HousemanshipApplicationDetailsModel();
            //delete existing details
            $applicationDetailsModel->builder()->where(['application_uuid' => $uuid])->delete();
            $applicationUuid = $uuid;

            $detailsValidationRules = [
                "first_choice" => "required|is_not_unique[housemanship_facilities.name]",
                "second_choice" => "required|is_not_unique[housemanship_facilities.name]|differs[first_choice]",
                "discipline" => "required|is_not_unique[housemanship_disciplines.name]"
            ];

            foreach ($details as $applicationDetail) {
                $applicationDetail = (array) $applicationDetail;
                $applicationDetail['application_uuid'] = $applicationUuid;
                $validation = \Config\Services::validation();

                if (!$validation->setRules($detailsValidationRules)->run($applicationDetail)) {
                    $message = implode(" ", array_values($validation->getErrors()));
                    return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
                }

                //get the facility details
                $facilityModel = new HousemanshipFacilitiesModel();
                $firstChoice = $facilityModel->where(['name' => $applicationDetail['first_choice']])->first();
                if (!$firstChoice) {
                    log_message("error", "First choice facility not found {$applicationDetail['first_choice']}");
                    $message = "First choice facility not found ";
                    return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
                }
                $secondChoice = $facilityModel->where(['name' => $applicationDetail['second_choice']])->first();

                if (!$secondChoice) {
                    log_message("error", "Second choice facility not found {$applicationDetail['second_choice']}");
                    $message = "Second choice facility not found ";
                    return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
                }
                $applicationDetail['first_choice_region'] = $firstChoice['region'];
                $applicationDetail['second_choice_region'] = $secondChoice['region'];


                $applicationDetailsModel->insert($applicationDetail);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated housemanship session {$data['session']} application for {$data['license_number']}", null, $this->activityModule);

            $model->db->transComplete();

            return $this->respond(['message' => "Housemanship application updated successfully for {$data['license_number']}", 'data' => ""], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }



    public function deleteHousemanshipPostingApplication($uuid)
    {
        try {
            $model = new HousemanshipApplicationModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted housemanship posting application for {$data['license_number']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Housemanship posting application deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function countHousemanshipPostingApplications()
    {
        try {

            $param = $this->request->getVar('param');
            $model = new HousemanshipApplicationModel();
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


    /**
     * get a single housemanship application with its details flattened. this will mostly be used for filling the form to edit the application
     * @param mixed $uuid
     * @throws \Exception
     * @return ResponseInterface
     */
    public function getHousemanshipPostingApplication($uuid)
    {
        try {
            $model = new HousemanshipApplicationModel();
            $detailsModel = new HousemanshipApplicationDetailsModel();
            $data = $model->where(["uuid" => $uuid])->first();
            if (!$data) {
                throw new Exception("Housemanship application not found");
            }
            $details = $detailsModel->where(["application_uuid" => $uuid])->findAll();
            for ($i = 0; $i < count($details); $i++) {
                $data["posting_application_detail-first_choice-$i"] = $details[$i]['first_choice'];
                $data["posting_application_detail-discipline-$i"] = $details[$i]['discipline'];
                $data["posting_application_detail-second_choice-$i"] = $details[$i]['second_choice'];
            }

            return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);

        }
    }

    public function getHousemanshipPostingApplications()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new HousemanshipApplicationModel();
            $detailsModel = new HousemanshipApplicationDetailsModel();

            $filterArray = $model->createArrayFromAllowedFields((array) $this->request->getGet());
            // Validate inputs here
            $tableName = $model->table;
            $builder = $param ? $model->search($param) : $model->builder()->select("$tableName.*");
            $builder = $model->addPractitionerDetailsFields($builder);
            array_map(function ($value, $key) use ($builder, $tableName) {
                $builder->where($tableName . "." . $key, $value);
            }, $filterArray, array_keys($filterArray));

            $builder->orderBy("$tableName.$sortBy", $sortOrder);

            if ($withDeleted) {
                $model->withDeleted();
            }

            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $displayColumns = $model->getDisplayColumns();
            // 1. Get parent records (housemanship applications)
            $parentRecords = $builder->get($per_page, $page)->getResult();

            // 2. Extract all parent IDs
            $parentIds = array_map(function ($record) {
                return $record->uuid;
            }, $parentRecords);

            // 3. Get all related child records in a single query
            $childRecords = [];
            if (!empty($parentIds)) {
                $childRecords = $detailsModel->whereIn('application_uuid', $parentIds)->findAll();
            }

            // 4. Group child records by parent_id for quick access
            $childrenByParentId = [];
            foreach ($childRecords as $child) {
                if (!isset($childrenByParentId[$child['application_uuid']])) {
                    $childrenByParentId[$child['application_uuid']] = [];
                }
                $childrenByParentId[$child['application_uuid']][] = $child;
            }

            // 5. Combine parent and child data
            foreach ($parentRecords as $parent) {
                $children = $childrenByParentId[$parent->uuid] ?? [];
                foreach ($children as $index => $child) {
                    // Convert child object to array to manipulate
                    $childArray = (array) $child;
                    foreach ($childArray as $key => $value) {
                        if (!in_array($key, ['application_uuid', 'id'])) { // Skip the join key
                            // Add child fields to parent
                            $fieldName = $key . "_" . ($index + 1);
                            if (!in_array($fieldName, $displayColumns)) {
                                $displayColumns[] = $fieldName;
                            }
                            $parent->$fieldName = $value;
                        }
                    }
                }
            }

            return $this->respond([
                'data' => $parentRecords,
                'total' => $total,
                'displayColumns' => $displayColumns,
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getHousemanshipPostingApplicationFormFields($session)
    {
        $model = new HousemanshipApplicationModel();
        $detailsModel = new HousemanshipApplicationDetailsModel();
        try {
            $sessionSetting = Utils::getHousemanshipSetting(HousemanshipSetting::SESSIONS);
            if (!$sessionSetting) {
                throw new Exception("Session setting not found");
            }
            if (!array_key_exists($session, $sessionSetting)) {
                throw new Exception("Session not found");
            }
            $numberOfRequiredFacilities = (int) $sessionSetting[$session]['number_of_facilities'];
            $mainFields = $model->getFormFields();
            $detailsFields = $detailsModel->getFormFields();
            //add the details fields to the main fields the number of times required
            for ($i = 0; $i < $numberOfRequiredFacilities; $i++) {
                $mainFields[] = [
                    "label" => "Discipline " . ($i + 1),
                    "name" => "",
                    "type" => "label",
                    "hint" => "",
                    "options" => [],
                    "value" => "",
                    "required" => false
                ];
                $detail = [];
                foreach ($detailsFields as $detailsField) {
                    $detailsField['name'] = "posting_application_detail-{$detailsField['name']}-$i";
                    $detail[] = $detailsField;
                }

                $mainFields[] = $detail;
            }
            return $this->respond([
                'data' => $mainFields
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // public function updateBulkPostings()
    // {
    //     try {

    //         $data = $this->request->getVar('data'); //an array of postings
    //         $letterTemplate = $this->request->getVar('letter_template');

    //         $results = [];
    //         foreach ($data as $renewal) {
    //             $renewal = (array) $renewal;
    //             $renewalUuid = $renewal['uuid'];
    //             $model = new LicenseRenewalModel();
    //             $existingRenewal = $model->builder()->where('uuid', $renewalUuid)->get()->getFirstRow('array');
    //             //get the license type renewal stage required data
    //             $licenseType = $existingRenewal['license_type'];



    //             unset($renewal['uuid']);
    //             if (!empty($status)) {
    //                 $rules = Utils::getLicenseRenewalStageValidation($licenseType, $status);
    //                 $validation = \Config\Services::validation();

    //                 if (!$validation->setRules($rules)->run($renewal)) {
    //                     throw new Exception("Validation failed");
    //                 }
    //                 $renewal['status'] = $status;
    //             }
    //             $model = new LicenseRenewalModel($licenseType);
    //             //start a transaction
    //             $model->db->transException(true)->transStart();

    //             LicenseUtils::updateRenewal(
    //                 $renewalUuid,
    //                 $renewal
    //             );

    //             $model->db->transComplete();
    //             $results[] = ['id' => $renewalUuid, 'successful' => true, 'message' => 'Renewal updated successfully'];

    //         }



    //         return $this->respond(['message' => 'Renewal updated successfully', 'data' => $results], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message("error", $th->getMessage());
    //         return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    // }
}
