<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CacheHelper;
use App\Helpers\Utils;
use App\Models\ApiIntegration\InstitutionModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class InstitutionsController extends ResourceController
{
    protected $modelName = InstitutionModel::class;
    protected $format = 'json';

    /**
     * Get list of institutions
     */
    public function index()
    {
        try {
            $cacheKey = Utils::generateHashedCacheKey("get_institutions", (array) $this->request->getVar());
            return CacheHelper::remember($cacheKey, function () {
                $userId = auth("tokens")->id();
                $user = AuthHelper::getAuthUser($userId);
                $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
                $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
                $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
                $param = $this->request->getVar('param');
                $sortBy = $this->request->getVar('sortBy') ?? "created_at";
                $sortOrder = $this->request->getVar('sortOrder') ?? "desc";
                $status = $this->request->getVar('status') ?? null;

                $model = new InstitutionModel();

                $builder = $param ? $model->search($param) : $model->builder();
                $tableName = $model->getTableName();

                if ($status) {
                    $builder->where("$tableName.status", $status);
                }

                $builder->orderBy("$tableName.$sortBy", $sortOrder);

                if ($withDeleted) {
                    $model->withDeleted();
                }

                $totalBuilder = clone $builder;
                $total = $totalBuilder->countAllResults();
                $result = $builder->get($per_page, $page)->getResult();
                $displayColumns = $model->getDisplayColumns();

                return $this->respond([
                    'data' => $result,
                    'total' => $total,
                    'displayColumns' => $displayColumns,
                    'columnFilters' => $model->getDisplayColumnFilters()
                ], ResponseInterface::HTTP_OK);
            });
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get single institution
     */
    public function show($id = null)
    {
        try {
            $model = new InstitutionModel();
            $institution = $model->getInstitutionWithKeyCount($id);

            if (!$institution) {
                return $this->respond(['message' => "Institution not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond($institution, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Create new institution
     */
    public function create()
    {
        try {
            $userId = auth("tokens")->id();
            $model = new InstitutionModel();

            $data = $this->request->getJSON(true);
            $data['created_by'] = $userId;

            // Validate IP whitelist if provided
            if (isset($data['ip_whitelist'])) {
                if (is_string($data['ip_whitelist'])) {
                    $ipWhitelist = json_decode($data['ip_whitelist'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return $this->respond([
                            'message' => "Invalid IP whitelist format. Must be valid JSON array."
                        ], ResponseInterface::HTTP_BAD_REQUEST);
                    }
                    $data['ip_whitelist'] = $ipWhitelist;
                }
            }

            $institutionId = $model->insert($data);

            if (!$institutionId) {
                return $this->respond([
                    'message' => "Failed to create institution",
                    'errors' => $model->errors()
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $institution = $model->find($institutionId);

            CacheHelper::invalidatePattern("get_institutions");

            return $this->respond([
                'message' => "Institution created successfully",
                'data' => $institution
            ], ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update institution
     */
    public function update($id = null)
    {
        try {
            $model = new InstitutionModel();
            $institution = $model->find($id);

            if (!$institution) {
                return $this->respond(['message' => "Institution not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $data = $this->request->getJSON(true);

            // Validate IP whitelist if provided
            if (isset($data['ip_whitelist'])) {
                if (is_string($data['ip_whitelist'])) {
                    $ipWhitelist = json_decode($data['ip_whitelist'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return $this->respond([
                            'message' => "Invalid IP whitelist format. Must be valid JSON array."
                        ], ResponseInterface::HTTP_BAD_REQUEST);
                    }
                    $data['ip_whitelist'] = $ipWhitelist;
                }
            }

            $success = $model->update($id, $data);

            if (!$success) {
                return $this->respond([
                    'message' => "Failed to update institution",
                    'errors' => $model->errors()
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $updatedInstitution = $model->find($id);

            CacheHelper::invalidatePattern("get_institutions");

            return $this->respond([
                'message' => "Institution updated successfully",
                'data' => $updatedInstitution
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Delete institution
     */
    public function delete($id = null)
    {
        try {
            $model = new InstitutionModel();
            $institution = $model->find($id);

            if (!$institution) {
                return $this->respond(['message' => "Institution not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            // Soft delete
            $success = $model->delete($id);

            if (!$success) {
                return $this->respond([
                    'message' => "Failed to delete institution"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            CacheHelper::invalidatePattern("get_institutions");

            return $this->respond([
                'message' => "Institution deleted successfully"
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get form fields for institution form
     */
    public function getFormFields()
    {
        try {
            $model = new InstitutionModel();
            $formFields = $model->getFormFields();

            return $this->respond($formFields, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get all institutions (simple list for dropdowns)
     */
    public function getInstitutionsList()
    {
        try {
            $model = new InstitutionModel();
            $institutions = $model->where('status', 'active')
                ->select('id, code, name')
                ->orderBy('name', 'ASC')
                ->findAll();

            return $this->respond($institutions, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }
}
