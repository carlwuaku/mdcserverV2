<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CacheHelper;
use App\Helpers\Utils;
use App\Models\ApiIntegration\ApiKeyModel;
use App\Models\ApiIntegration\ApiKeyPermissionModel;
use App\Models\ApiIntegration\ApiRequestLogModel;
use App\Services\ApiKeyService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ApiKeysController extends ResourceController
{
    protected $modelName = ApiKeyModel::class;
    protected $format = 'json';
    private ApiKeyService $apiKeyService;

    public function __construct()
    {
        $this->apiKeyService = new ApiKeyService();
    }

    /**
     * Get list of API keys
     */
    public function index()
    {
        try {
            $cacheKey = Utils::generateHashedCacheKey("get_api_keys", (array) $this->request->getVar());
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
                $institutionId = $this->request->getVar('institution_id') ?? null;

                $model = new ApiKeyModel();

                $builder = $param ? $model->search($param) : $model->builder();
                $builder = $model->addCustomFields($builder);
                $tableName = $model->getTableName();

                if ($status) {
                    $builder->where("$tableName.status", $status);
                }

                if ($institutionId) {
                    $builder->where("$tableName.institution_id", $institutionId);
                }

                $builder->orderBy("$tableName.$sortBy", $sortOrder);

                if ($withDeleted) {
                    $model->withDeleted();
                }

                $totalBuilder = clone $builder;
                $total = $totalBuilder->countAllResults();
                $result = $builder->get($per_page, $page)->getResult();
                $displayColumns = $model->getDisplayColumns();

                // Remove sensitive fields from response
                foreach ($result as &$key) {
                    unset($key->key_secret_hash);
                    unset($key->hmac_secret);
                }

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
     * Get single API key
     */
    public function show($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            // Remove sensitive fields
            unset($apiKey['key_secret_hash']);
            unset($apiKey['hmac_secret']);

            // Get permissions
            $apiKey['permissions'] = $this->apiKeyService->getKeyPermissions($id);

            return $this->respond($apiKey, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Generate new API key
     */
    public function create()
    {
        try {
            $userId = auth("tokens")->id();
            $data = $this->request->getJSON(true);

            if (empty($data['institution_id'])) {
                return $this->respond([
                    'message' => "Institution ID is required"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (empty($data['name'])) {
                return $this->respond([
                    'message' => "API key name is required"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Parse JSON fields if they're strings
            if (isset($data['scopes']) && is_string($data['scopes'])) {
                $data['scopes'] = json_decode($data['scopes'], true);
            }

            if (isset($data['allowed_endpoints']) && is_string($data['allowed_endpoints'])) {
                $data['allowed_endpoints'] = json_decode($data['allowed_endpoints'], true);
            }

            if (isset($data['metadata']) && is_string($data['metadata'])) {
                $data['metadata'] = json_decode($data['metadata'], true);
            }

            $permissions = $data['permissions'] ?? [];
            unset($data['permissions']);

            // Create API key
            $apiKey = $this->apiKeyService->createApiKey(
                $data['institution_id'],
                $data,
                $permissions,
                $userId
            );

            if (!$apiKey) {
                return $this->respond([
                    'message' => "Failed to create API key"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            CacheHelper::invalidatePattern("get_api_keys");

            // Return key with secrets (ONLY TIME THEY'RE SHOWN)
            return $this->respond([
                'message' => "API key created successfully",
                'data' => $apiKey,
                'warning' => "IMPORTANT: Save the key_secret and hmac_secret_plaintext now. They will never be shown again!"
            ], ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update API key
     */
    public function update($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $data = $this->request->getJSON(true);

            // Parse JSON fields
            if (isset($data['scopes']) && is_string($data['scopes'])) {
                $data['scopes'] = json_decode($data['scopes'], true);
            }

            if (isset($data['allowed_endpoints']) && is_string($data['allowed_endpoints'])) {
                $data['allowed_endpoints'] = json_decode($data['allowed_endpoints'], true);
            }

            if (isset($data['metadata']) && is_string($data['metadata'])) {
                $data['metadata'] = json_decode($data['metadata'], true);
            }

            // Update permissions if provided
            if (isset($data['permissions'])) {
                $this->apiKeyService->updatePermissions($id, $data['permissions']);
                unset($data['permissions']);
            }

            // Don't allow updating sensitive fields
            unset($data['key_id']);
            unset($data['key_secret_hash']);
            unset($data['hmac_secret']);
            unset($data['institution_id']);

            $success = $model->update($id, $data);

            if (!$success) {
                return $this->respond([
                    'message' => "Failed to update API key",
                    'errors' => $model->errors()
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $updatedKey = $model->find($id);
            unset($updatedKey['key_secret_hash']);
            unset($updatedKey['hmac_secret']);

            CacheHelper::invalidatePattern("get_api_keys");

            return $this->respond([
                'message' => "API key updated successfully",
                'data' => $updatedKey
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error: " . $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Revoke API key
     */
    public function revoke($id = null)
    {
        try {
            $userId = auth("tokens")->id();
            $data = $this->request->getJSON(true);
            $reason = $data['reason'] ?? 'Revoked by administrator';

            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            if ($apiKey['status'] === 'revoked') {
                return $this->respond(['message' => "API key is already revoked"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $success = $this->apiKeyService->revokeKey($id, $reason, $userId);

            if (!$success) {
                return $this->respond([
                    'message' => "Failed to revoke API key"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            CacheHelper::invalidatePattern("get_api_keys");

            return $this->respond([
                'message' => "API key revoked successfully"
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Delete API key (soft delete)
     */
    public function delete($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $success = $model->delete($id);

            if (!$success) {
                return $this->respond([
                    'message' => "Failed to delete API key"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            CacheHelper::invalidatePattern("get_api_keys");

            return $this->respond([
                'message' => "API key deleted successfully"
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Rotate API key (generate new credentials)
     */
    public function rotate($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $rotatedKey = $this->apiKeyService->rotateKey($id);

            if (!$rotatedKey) {
                return $this->respond([
                    'message' => "Failed to rotate API key"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            CacheHelper::invalidatePattern("get_api_keys");

            return $this->respond([
                'message' => "API key rotated successfully",
                'data' => $rotatedKey,
                'warning' => "IMPORTANT: Save the new key_secret now. It will never be shown again!"
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get API key usage statistics
     */
    public function getStats($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');

            $logModel = new ApiRequestLogModel();
            $stats = $logModel->getStatsByApiKey($id, $startDate, $endDate);

            return $this->respond($stats, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get API key request logs
     */
    public function getLogs($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $limit = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;

            $logModel = new ApiRequestLogModel();
            $logs = $logModel->getRecentByApiKey($id, $limit);

            return $this->respond([
                'data' => $logs,
                'total' => count($logs)
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get form fields for API key form
     */
    public function getFormFields()
    {
        try {
            $model = new ApiKeyModel();
            $formFields = $model->getFormFields();

            return $this->respond($formFields, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get integration documentation for API key
     */
    public function getDocumentation($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $documentation = $this->apiKeyService->generateDocumentation($id);

            return $this->respond($documentation, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get available permissions
     */
    public function getAvailablePermissions()
    {
        try {
            // Get all permissions from permissions table
            $permissionsModel = new \App\Models\PermissionsModel();
            $permissions = $permissionsModel->where('status', 'active')
                ->select('name, description')
                ->orderBy('name', 'ASC')
                ->findAll();

            return $this->respond($permissions, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update API key permissions
     */
    public function updatePermissions($id = null)
    {
        try {
            $model = new ApiKeyModel();
            $apiKey = $model->find($id);

            if (!$apiKey) {
                return $this->respond(['message' => "API key not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            $data = $this->request->getJSON(true);
            $permissions = $data['permissions'] ?? [];

            if (!is_array($permissions)) {
                return $this->respond([
                    'message' => "Permissions must be an array"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $success = $this->apiKeyService->updatePermissions($id, $permissions);

            if (!$success) {
                return $this->respond([
                    'message' => "Failed to update permissions"
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            return $this->respond([
                'message' => "Permissions updated successfully",
                'permissions' => $permissions
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }
}
