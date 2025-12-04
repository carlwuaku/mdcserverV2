<?php

namespace App\Models\ApiIntegration;

use App\Models\MyBaseModel;

class ApiKeyPermissionModel extends MyBaseModel
{
    protected $table = 'api_key_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'api_key_id',
        'permission',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $deletedField = null;

    // Validation
    protected $validationRules = [
        'api_key_id' => 'required|max_length[36]',
        'permission' => 'required|max_length[255]',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get permissions for an API key
     */
    public function getPermissions(string $apiKeyId): array
    {
        $results = $this->where('api_key_id', $apiKeyId)->findAll();
        return array_column($results, 'permission');
    }

    /**
     * Check if API key has specific permission
     */
    public function hasPermission(string $apiKeyId, string $permission): bool
    {
        return $this->where('api_key_id', $apiKeyId)
            ->where('permission', $permission)
            ->countAllResults() > 0;
    }

    /**
     * Set permissions for an API key (replaces existing)
     */
    public function setPermissions(string $apiKeyId, array $permissions): bool
    {
        // Delete existing permissions
        $this->where('api_key_id', $apiKeyId)->delete();

        // Insert new permissions
        if (empty($permissions)) {
            return true;
        }

        $data = [];
        foreach ($permissions as $permission) {
            $data[] = [
                'api_key_id' => $apiKeyId,
                'permission' => $permission,
            ];
        }

        return $this->insertBatch($data) !== false;
    }

    /**
     * Add permission to API key
     */
    public function addPermission(string $apiKeyId, string $permission): bool
    {
        // Check if already exists
        if ($this->hasPermission($apiKeyId, $permission)) {
            return true;
        }

        return $this->insert([
            'api_key_id' => $apiKeyId,
            'permission' => $permission,
        ]) !== false;
    }

    /**
     * Remove permission from API key
     */
    public function removePermission(string $apiKeyId, string $permission): bool
    {
        return $this->where('api_key_id', $apiKeyId)
            ->where('permission', $permission)
            ->delete();
    }
}
