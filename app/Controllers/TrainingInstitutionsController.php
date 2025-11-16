<?php

namespace App\Controllers;

use App\Models\TrainingInstitutions\TrainingInstitutionModel;
use App\Models\TrainingInstitutions\TrainingInstitutionLimitModel;
use App\Models\TrainingInstitutions\StudentIndexModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\CacheHelper;
use App\Traits\CacheInvalidatorTrait;
use App\Helpers\Utils;
use App\Models\ActivitiesModel;
use \Exception;
class TrainingInstitutionsController extends ResourceController
{
    use CacheInvalidatorTrait;
    private $activityModule = "training_institutions";

    public function getTrainingInstitutionSetting(string $setting)
    {
        try {

            $cacheKey = Utils::generateHashedCacheKey('training_institutions_', [$setting]);
            return CacheHelper::remember($cacheKey, function () use ($setting) {
                $settings = Utils::getTrainingInstitutionsSettings();
                $result = null;
                switch ($setting) {
                    case "practitioner_types":
                        $result = $settings::$practitionerTypes;
                        break;
                    default:
                        throw new Exception("$setting not found in training institutions settings");
                }

                return $this->respond([
                    'data' => $result,

                ], ResponseInterface::HTTP_OK);
            });
        } catch (Exception $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTrainingInstitutions()
    {
        try {
            $filters = $this->extractRequestFilters();
            $cacheKey = Utils::generateHashedCacheKey('training_institutions_', $filters);
            return CacheHelper::remember($cacheKey, function () use ($filters) {
                $per_page = $filters['limit'] ?? 100;
                $page = $filters['page'] ?? 0;
                $withDeleted = ($filters['withDeleted'] ?? '') === "yes";
                $param = $filters['param'] ?? $filters['child_param'] ?? null;
                $sortBy = $filters['sortBy'] ?? "id";
                $sortOrder = $filters['sortOrder'] ?? "asc";


                $model = new TrainingInstitutionModel();


                $builder = $param ? $model->search($param) : $model->builder();

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
            });



        } catch (Exception $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }



    /**
     * Get a single training institution by UUID with student count
     */
    public function getTrainingInstitution($uuid)
    {
        $model = new TrainingInstitutionModel();
        $data = $model->where(["uuid" => $uuid])->first();
        if (!$data) {
            return $this->respond("Training institution not found", ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    /**
     * Create a new training institution
     */
    public function createTrainingInstitution()
    {
        try {
            $rules = [
                "name" => "required|is_unique[training_institutions.name]",
                "type" => "required",
                "region" => "required|is_not_unique[regions.name]",
                "email" => "permit_empty|valid_email",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getPost();

            $model = new TrainingInstitutionModel();
            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            // Invalidate cache
            $this->invalidateCache('training_institutions_');
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created housemanship facility {$data['name']}.", null, $this->activityModule);

            return $this->respond(['message' => 'Training institution created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a training institution by UUID
     */
    public function updateTrainingInstitution($uuid)
    {
        try {
            $model = new TrainingInstitutionModel();
            $existing = $model->where('uuid', $uuid)->first();

            if (!$existing) {
                return $this->respond(['message' => 'Training institution not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            $data = (array) $this->request->getVar();

            // Validate unique name if it's being changed
            if (isset($data['name']) && $data['name'] !== $existing['name']) {
                $rules = [
                    "name" => "required|is_unique[training_institutions.name]"
                ];

                if (!$this->validate($rules)) {
                    return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
                }
            }

            if (!$model->update($existing['id'], $data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate cache
            $this->invalidateCache('training_institutions_');

            return $this->respond(['message' => 'Training institution updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a training institution by UUID
     */
    public function deleteTrainingInstitution($uuid)
    {
        try {
            $model = new TrainingInstitutionModel();
            $data = $model->where(["uuid" => $uuid])->first();
            if (!$data) {
                return $this->respond(['message' => "Housemanship facility not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted Housemanship facility {$data['name']}.", null, $this->activityModule);
            // Invalidate cache
            $this->invalidateCache('training_institutions_');
            return $this->respond(['message' => 'Housemanship facility deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get limits for a training institution
     */
    public function getInstitutionLimits($uuid)
    {
        try {
            $model = new TrainingInstitutionLimitModel();
            $limits = $model->getLimitsByInstitution($uuid);

            return $this->respond(['data' => $limits], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Set or update limit for a training institution for a specific year
     */
    public function setInstitutionLimit($uuid)
    {
        try {
            $rules = [
                "year" => "required|numeric",
                "student_limit" => "required|numeric|greater_than_equal_to[0]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Verify institution exists
            $institutionModel = new TrainingInstitutionModel();
            $institution = $institutionModel->where('uuid', $uuid)->first();

            if (!$institution) {
                return $this->respond(['message' => 'Training institution not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            $data = (array) $this->request->getVar();
            $limitModel = new TrainingInstitutionLimitModel();

            $result = $limitModel->setLimit($uuid, $data['year'], $data['student_limit']);

            if (!$result) {
                return $this->respond(['message' => $limitModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate cache
            $this->invalidateCache('training_institutions_');

            return $this->respond(['message' => 'Limit set successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a limit for a training institution for a specific year
     */
    public function deleteInstitutionLimit($uuid, $year)
    {
        try {
            $limitModel = new TrainingInstitutionLimitModel();
            $limit = $limitModel->getLimitByInstitutionAndYear($uuid, $year);

            if (!$limit) {
                return $this->respond(['message' => 'Limit not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            if (!$limitModel->delete($limit['id'])) {
                return $this->respond(['message' => $limitModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate cache
            $this->invalidateCache('training_institutions_');

            return $this->respond(['message' => 'Limit deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get students for a training institution by year
     */
    public function getInstitutionStudents($uuid)
    {
        try {
            $year = $this->request->getVar('year') ?? date('Y');

            // Get institution to get the name
            $institutionModel = new TrainingInstitutionModel();
            $institution = $institutionModel->where('uuid', $uuid)->first();

            if (!$institution) {
                return $this->respond(['message' => 'Training institution not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            $studentModel = new StudentIndexModel();
            $students = $studentModel->getByInstitutionAndYear($institution['name'], $year);

            return $this->respond(['data' => $students], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function extractRequestFilters(): array
    {

        $filters = [];
        //merge get and post data
        $filters = array_merge($this->request->getGet(), (array) $this->request->getVar());


        return $filters;
    }
}
